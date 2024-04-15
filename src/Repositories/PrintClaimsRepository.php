<?php
declare(strict_types=1);

namespace Crm\PrintModule\Repositories;

use Crm\ApplicationModule\Models\Database\Repository;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class PrintClaimsRepository extends Repository
{
    protected $tableName = 'print_claims';

    public function add(
        ActiveRow $printSubscription,
        string $description,
        string $claimant,
        string $claimantContact
    ) {
        $now = new \DateTime();

        return $this->insert([
            'print_subscription_id' => $printSubscription->id,
            'description' => $description,
            'claimant' => $claimant,
            'claimant_contact' => $claimantContact,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function update(ActiveRow &$row, $data): bool
    {
        $data['updated_at'] = new \DateTime();

        return parent::update($row, $data);
    }

    public function close(ActiveRow $row): bool
    {
        $data['closed_at'] = new \DateTime();

        return $this->update($row, $data);
    }

    public function all(
        string $claimant = null,
        string $status = null,
        string $typeGroup = null,
        string $from = null,
        string $to = null,
    ): Selection {
        $selection = $this->getTable()->order('created_at DESC');

        if ($claimant) {
            $selection->where('(claimant LIKE ?) OR (claimant_contact LIKE ?)', "%$claimant%", "%$claimant%");
        }

        if ($status === 'closed') {
            $selection->where('closed_at IS NOT NULL');
        }

        if ($status === 'open') {
            $selection->where('closed_at IS NULL');
        }

        if ($typeGroup) {
            $selection->where('print_subscription.type LIKE ?', "{$typeGroup}%");
        }

        if ($from) {
            $selection->where('created_at >= ?', $from);
        }

        if ($to) {
            $selection->where('created_at <= ?', $to);
        }

        return $selection;
    }
}
