<?php

namespace Crm\PrintModule\Repositories;

use Crm\ApplicationModule\Models\Database\Repository;
use Crm\ApplicationModule\Models\Database\Selection;
use Crm\ApplicationModule\Models\NowTrait;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;

class AddressRedirectsRepository extends Repository
{
    use NowTrait;

    protected $tableName = 'address_redirects';

    public function __construct(
        Explorer $database,
    ) {
        parent::__construct($database);
    }

    final public function add(
        ActiveRow $originalAddress,
        ActiveRow $redirectAddress,
        DateTime $from,
        DateTime $to,
        ?string $note,
    ) {

        return $this->insert([
            'original_address_id' => $originalAddress->id,
            'redirect_address_id' => $redirectAddress->id,
            'from' => $from,
            'to' => $to,
            'note' => $note,
            'created_at' => $this->getNow(),
            'updated_at' => $this->getNow(),
        ]);
    }

    final public function update(ActiveRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function getAddressRedirects($addressId): Selection
    {
        return $this->getTable()->where([
            'original_address_id' => $addressId,
        ]);
    }

    public function getCurrentRedirects(): Selection
    {
        $now = $this->getNow();
        return $this->getTable()->where([
            'from <= ?' => $now,
            'to > ?' => $now,
        ]);
    }

    public function getAddressCurrentRedirect($addressId): Selection
    {
        $now = $this->getNow();
        return $this->getTable()->where([
            'original_address_id' => $addressId,
            'from <= ?' => $now,
            'to > ?' => $now,
        ]);
    }

    public function getAddressesRedirectedTo($addressId): Selection
    {
        return $this->getTable()->where([
            'redirect_address_id' => $addressId,
        ]);
    }
}
