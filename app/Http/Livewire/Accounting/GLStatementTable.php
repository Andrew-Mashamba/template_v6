<?php

namespace App\Http\Livewire\Accounting;

use App\Models\AccountsModel;
use App\Models\Branches;
use App\Models\general_ledger;
use App\Models\Role;
use App\Models\Employee;
use App\Models\GeneralLedger; // Ensure your model follows correct naming convention (e.g., CamelCase)
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class GLStatementTable extends LivewireDatatable
{
    protected $listeners = ['refreshMembersTable' => '$refresh'];
    public $exportable = true;

    /**
     * Build the query for the datatable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function builder()
    {
        // Make sure the GeneralLedger model uses the correct case
        return general_ledger::query();
    }

    /**
     * Define columns for the datatable.
     *
     * @return array
     */
    public function columns(): array
    {
        return [
            Column::name('created_at')
                ->label('Date')
                ->searchable(),

            Column::name('record_on_account_number')
                ->label('Account')
                ->searchable(),

            Column::callback(['id'], function ($id) {
                // Retrieve account name and type from AccountsModel or AccountsModel
                $accountNumber = general_ledger::where('id', $id)->value('record_on_account_number');

                $accountName = AccountsModel::where('account_number', $accountNumber)
                    ->value('account_name');
                $type = AccountsModel::where('account_number', $accountNumber)
                    ->value('type');

                // If not found in AccountsModel, search in AccountsModel
                if (!$accountName) {
                    $accountName = AccountsModel::where('account_number', $accountNumber)
                        ->value('account_name');
                    $type = AccountsModel::where('account_number', $accountNumber)
                        ->value('type');
                }

                // Return formatted account type and name
                return $type . ' : ' . $accountName;
            })
                ->label('Account Name')
                ->searchable(),

            Column::name('narration')
                ->label('Narration')
                ->searchable(),

            Column::name('credit')
                ->label('Credit')
                ->searchable(),

            Column::name('debit')
                ->label('Debit')
                ->searchable(),
        ];
    }
}
