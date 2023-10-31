<?php

namespace Crm\PrintModule\Repository;

use Crm\ApplicationModule\NowTrait;
use Crm\ApplicationModule\Repository;
use Crm\PrintModule\User\PrintAddressesUserDataProvider;
use Crm\UsersModule\Repository\AddressesRepository;
use Crm\UsersModule\Repository\CountriesRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\User\AddressesUserDataProvider;
use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

class PrintSubscriptionsRepository extends Repository
{
    use NowTrait;

    public const STATUS_NEW = 'new';
    public const STATUS_REMOVED = 'removed';
    public const STATUS_RECURRENT = 'recurrent';

    protected $tableName = 'print_subscriptions';

    public function __construct(
        Explorer $database,
        private AddressesRepository $addressesRepository,
        private UsersRepository $usersRepository,
        private CountriesRepository $countriesRepository,
        Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
    }

    final public function getAllCounts(string $type, int $year)
    {
        $nextYear = $year + 1;
        $allStatusCounts = $this->getTable()
            ->select('exported_at, export_date, status, count(*) AS "total", SUM(COALESCE(JSON_EXTRACT(meta, "$.item_count"), 1)) AS "delivering_pcs"')
            ->where('type', $type)
            ->where(['export_date >= ?' => DateTime::from(strtotime("1.1.{$year} 00:00:00"))])
            ->where(['export_date < ?' => DateTime::from(strtotime("1.1.{$nextYear} 00:00:00"))])
            ->group('export_date, exported_at, status')
            ->order('export_date DESC');

        $counts = [];
        foreach ($allStatusCounts as $row) {
            $printDate = $row->export_date->format('Y-m-d');
            if (!isset($counts[$printDate])) {
                $counts[$printDate] = [
                    'delivering_pcs' => 0,
                ];
            }

            if (in_array($row->status, ['new', 'recurrent'], true)) {
                $counts[$printDate]['delivering_pcs'] += $row->delivering_pcs;
            }

            $counts[$printDate]['exported_at'] = $row->exported_at->format('Y-m-d');
            $counts[$printDate][$row->status] = $row->total;
        }
        return $counts;
    }

    final public function firstExport(string $type)
    {
        return $this->getTable()
            ->where(['type' => $type])
            ->order('export_date ASC')
            ->limit(1)
            ->fetch();
    }

    final public function lastExport(string $type)
    {
        return $this->getTable()
            ->where(['type' => $type])
            ->order('export_date DESC')
            ->limit(1)
            ->fetch();
    }

    final public function add(
        $type,
        $subscriptionId,
        ActiveRow $user,
        \DateTime $exportDate,
        ActiveRow $address = null,
        $status = 'new',
        $exportAt = null,
        $meta = 'null'
    ) {
        if ($meta === "[]") {
            $meta = "{}";
        }
        return $this->insert([
            'type' => $type,
            'subscription_id' => $subscriptionId,
            'user_id' => $user->id,
            'exported_at' => $exportAt ?: new DateTime(),
            'export_date' => $exportDate->format('Y-m-d'),
            'institution_name' => $address->company_name ?? $user->institution_name,
            'first_name' => $address?->first_name,
            'last_name' => $address?->last_name,
            'address' => $address?->address,
            'number' => $address?->number,
            'zip' => $address?->zip,
            'city' => $address?->city,
            'phone_number' => $address?->phone_number,
            'country_id' => $address?->country_id ?? $this->countriesRepository->defaultCountry()->id,
            'email' => $user->email ?? $user->public_name,
            'status' => $status,
            'address_id' => $address?->id,
            'meta' => $meta,
        ]);
    }

    final public function getTypes()
    {
        return $this->getTable()->select('DISTINCT type')->fetchPairs('type', 'type');
    }

    final public function getExport($type, $date, $text = '', $status = '')
    {
        $where = ['type' => $type];
        if ($text != '') {
            $where['address LIKE ? OR first_name LIKE ? OR last_name LIKE ?'] = [
                "%{$text}%",
                "%{$text}%",
                "%{$text}%"
            ];
        }
        if ($status) {
            $where['status'] = $status;
        }
        $where['export_date'] = $date;
        return $this->getTable()->where($where)->order('status, first_name, last_name, address ASC');
    }

    final public function getExportData($type, \DateTime $exportDate, \DateTime $printDate = null)
    {
        $where = [];
        $where['type'] = $type;
        $where['export_date'] = $exportDate->format('Y-m-d');
        if ($printDate) {
            $where['DATE(exported_at)'] = $printDate->format('Y-m-d');
        }
        return $this->getTable()->where($where)->order('first_name, last_name, address ASC');
    }

    final public function deleteList($type, $date)
    {
        $where = [
            'export_date' => $date,
            'type' => $type,
        ];
        return $this->getTable()->where($where)->delete();
    }

    final public function removeUnusedPrintAddresses()
    {
        // check deleted users' (print subscriptions) addresses
        // if all user's print subscription addresses are unused (=last export for each type has status 'removed'), we can safely delete such addresses
        $sql = <<<SQL
-- anonymize all addresses that were "removed" from their exports
SELECT t2.address_id from 
  (SELECT ps.type, ps.user_id, MAX(ps.export_date) AS export_date 
    FROM addresses a 
    JOIN users u ON u.id = a.user_id 
       AND a.deleted_at IS NULL 
       AND u.deleted_at IS NOT NULL
    JOIN print_subscriptions ps ON ps.address_id = a.id
    GROUP BY ps.type, ps.user_id) t1 
  JOIN print_subscriptions t2 ON t1.user_id = t2.user_id AND t1.export_date = t2.export_date AND t1.type = t2.type
  GROUP BY t2.address_id
  HAVING SUM(CASE WHEN t2.status != ? THEN 1 else 0 END) = 0

UNION

-- anonymize addresses of deleted users not used for more than 6 months (in case they weren't matched by status=removed above)
SELECT address_id
FROM print_subscriptions
INNER JOIN users
   ON user_id = users.id
   AND deleted_at IS NOT NULL
GROUP BY address_id
HAVING MAX(export_date) < NOW() - INTERVAL 6 month
SQL;

        $gdprRemovalTemplate = AddressesUserDataProvider::gdprRemovalTemplate($this->getNow());

        foreach ($this->getDatabase()->query($sql, self::STATUS_REMOVED) as $row) {
            $addressRow = $this->addressesRepository->find($row->address_id);
            if ($addressRow) {
                $this->addressesRepository->update($addressRow, $gdprRemovalTemplate);

                $printSubscriptionGdprRemovalTemplate = PrintAddressesUserDataProvider::gdprRemovalTemplate();
                $this->getTable()->where('address_id', $addressRow->id)->update($printSubscriptionGdprRemovalTemplate);
            }
        }
    }

    final public function setPrintExportStatus($type, \DateTime $printExportDate, \DateTime $exportAt)
    {
        $date = $this->getTable()->where(['type' => $type])->select('export_date')->group('export_date')->order('export_date DESC')->limit(1, 1);
        if (!isset($date[0])) {
            return;
        }
        $previousExportDate = $date[0]->export_date;
        $printExportDate->setTime(0, 0);

        $temp = $this->getTable()->select('user_id')
            ->where(['type' => $type])
            ->where('export_date', $previousExportDate)
            ->where('status != ?', self::STATUS_REMOVED);

        $this->getTable()
            ->where('user_id', $temp)
            ->where(['type' => $type])
            ->where('export_date', $printExportDate->format('Y-m-d'))
            ->update(['status' => self::STATUS_RECURRENT]);

        $temp = $this->getTable()
            ->select('user_id')
            ->where(['type' => $type])
            ->where('export_date', $printExportDate->format('Y-m-d'));

        $printEnded = $this->getTable()
            ->select('*')
            ->where(['type' => $type])
            ->where('user_id NOT', $temp)
            ->where('export_date', $previousExportDate)
            ->where('status != ?', self::STATUS_REMOVED);

        foreach ($printEnded as $row) {
            $this->add(
                type: $type,
                subscriptionId: $row->subscription_id,
                user: $row->user,
                exportDate: $printExportDate,
                address: $row->addr,
                status: self::STATUS_REMOVED,
                exportAt: $exportAt
            );
        }
    }

    /**
     * Kopia funkcie hore
     *  - namiesto s user_id pracuje so subscription_id - co je asi spravne ale som sa bal to menit v tej hornej funkcii pre dennikn
     *
     */
    final public function setPrintExportStatusTyzdenSpecial($type, \DateTime $printExportDate, \DateTime $exportAt)
    {
        $previousExportDate = clone $printExportDate;
        $previousExportDate->sub(new \DateInterval('P1D'));
        $previousExportDate->setTime(0, 0);

        $printExportDate->setTime(0, 0);

        $temp = $this->getTable()->select('subscription_id')
            ->where(['type' => $type])
            ->where('export_date', $previousExportDate)
            ->where('status != ?', 'removed');

        $this->getTable()
            ->where('subscription_id', $temp)
            ->where(['type' => $type])
            ->where('export_date', $printExportDate)
            ->update(['status' => 'recurrent']);

        $temp = $this->getTable()
            ->select('subscription_id')
            ->where(['type' => $type])
            ->where('export_date', $printExportDate);

        $printEnded = $this->getTable()
            ->select('*')
            ->where(['type' => $type])
            ->where('subscription_id NOT', $temp)
            ->where('export_date', $previousExportDate)
            ->where('status != ?', 'removed');

        foreach ($printEnded as $row) {
            $this->add(
                type: $type,
                subscriptionId: $row->subscription_id,
                user: $row->user,
                exportDate: $printExportDate,
                address: $row->addr,
                status: self::STATUS_REMOVED,
                exportAt: $exportAt
            );
        }
    }

    /**
     * Kopia funkcie hore
     * ble ble ble - meni fungovanie pridavanie expirovanych
     *
     */
    final public function setPrintExportStatusTyzdenDaily($type, \DateTime $printExportDate, \DateTime $exportAt)
    {
        $previousExportDate = clone $printExportDate;
        $previousExportDate->sub(new \DateInterval('P1D'));
        $previousExportDate->setTime(0, 0);

        $printExportDate->setTime(0, 0);

        $temp = $this->getTable()->select('subscription_id')
            ->where(['type' => $type])
            ->where('export_date', $previousExportDate)
            ->where('status != ?', 'removed');

        $this->getTable()
            ->where('subscription_id', $temp)
            ->where(['type' => $type])
            ->where('export_date', $printExportDate)
            ->update(['status' => 'recurrent']);

        // nastavime na recurrent userov ktorym sa zmenilo predplatne (cize uz to neupdatla query vyssie) ale presli na dalsie
        // aktualne pri predlzeni ich to davalo stale ako new

        $oldDate = clone $printExportDate;
        $oldDate->sub(new \DateInterval('P13D'));

        $query = "
SELECT
  today.id,
  today.user_id AS today_user_id, today.subscription_id AS today_subscription_id ,
  yesterday.user_id AS yesterday_user_id, yesterday.subscription_id AS yesterday_subscription_id
FROM print_subscriptions AS today
INNER JOIN subscriptions AS today_subscription ON today_subscription.id = today.subscription_id
INNER JOIN print_subscriptions AS yesterday ON yesterday.export_date >= '{$oldDate->format('Y-m-d H:i:s')}' AND yesterday.export_date < '{$printExportDate->format('Y-m-d H:i:s')}' AND yesterday.user_id = today.user_id AND yesterday.type='{$type}'
INNER JOIN subscriptions AS yesterday_subscription ON yesterday_subscription.id = yesterday.subscription_id
WHERE today.export_date = '{$printExportDate->format('Y-m-d H:i:s')}' AND yesterday.status = 'recurrent' AND today.status = 'new' AND today.type='{$type}'
 -- vyradime tie ktore uz boli 'removed' aby ich nedalo znovu ako rekurentne lebo musia byt ako nove
 AND yesterday.subscription_id NOT IN (SELECT subscription_id FROM print_subscriptions WHERE subscription_id = yesterday.subscription_id AND status='removed')
GROUP BY today.id
";

        // var_dump($query);

        $rows = $this->getDatabase()->query($query);

        // zrusil som 'AND yesterday_subscription.next_subscription_id = today_subscription.id'
        foreach ($rows as $row) {
            echo "updatujem {$row->id} na recurrent\n";
            $this->getTable()->where(['id' => $row->id])->update(['status' => 'recurrent']);
        }
        
        $date = $printExportDate->format('Y-m-d 6:10');

//        $rows = $this->getDatabase()->query("SELECT end_subscription.*
//          FROM subscriptions AS end_subscription
//          INNER JOIN subscription_types AS end_subscription_type ON end_subscription_type.id = end_subscription.subscription_type_id AND end_subscription_type.print = 1
//          LEFT JOIN subscriptions AS next_subscription ON next_subscription.user_id = end_subscription.user_id AND next_subscription.end_time > end_subscription.end_time AND next_subscription.start_time < '{$date}' + INTERVAL 1 DAY
//          LEFT JOIN subscription_types AS next_subscription_type ON next_subscription_type.id = next_subscription.subscription_type_id AND next_subscription_type.print = 1
//          WHERE
//            end_subscription.end_time > '{$date}' - INTERVAL 1 DAY AND end_subscription.end_time < '{$date}'  AND
//            next_subscription_type.id IS NULL AND next_subscription.id IS NULL AND end_subscription.next_subscription_id IS NULL
//        ");

        $rows = $this->getDatabase()->query("SELECT end_subscription.*
          FROM subscriptions AS end_subscription
          INNER JOIN subscription_types AS end_subscription_type ON end_subscription_type.id = end_subscription.subscription_type_id AND end_subscription_type.print = 1
          WHERE
            end_subscription.end_time > '{$date}' - INTERVAL 1 DAY AND end_subscription.end_time < '{$date}' AND
            ( 
              SELECT subscriptions.id 
              FROM subscriptions 
              INNER JOIN subscription_types ON subscription_types.id = subscriptions.subscription_type_id AND subscription_types.print = 1
              WHERE subscriptions.end_time > end_subscription.end_time AND 
                    subscriptions.start_time < '{$date}' AND 
                    subscriptions.user_id = end_subscription.user_id
              LIMIT 1
            ) IS NULL
        ");

        foreach ($rows as $row) {
            $user = $this->usersRepository->find($row->user_id);
            $address = null;
            if ($row->address_id) {
                $address = $this->addressesRepository->find($row->address_id);
            }
            if (!$address) {
                $address = $this->addressesRepository->address($user, 'print');
            }
            if (!$address) {
                $address = null;
            }
            $this->add(
                type: $type,
                subscriptionId: $row->id,
                user: $user,
                exportDate: $printExportDate,
                address: $address,
                status: self::STATUS_REMOVED,
                exportAt: $exportAt
            );
        }
    }

    final public function userPrintSubscriptions($userId)
    {
        return $this->getTable()->where(['user_id' => $userId])->order('export_date DESC');
    }

    final public function getLatestExportDate()
    {
        $row = $this->getTable()->select('MAX(export_date) AS max_export_date')->fetch();
        return $row->max_export_date;
    }
}
