<?php

// Get all seeder files
$seeders = [];
$files = glob(__DIR__ . '/database/seeders/*.php');

foreach ($files as $file) {
    $filename = basename($file);
    if ($filename !== 'DatabaseSeeder.php') {
        $classname = str_replace('.php', '', $filename);
        $seeders[] = $classname . '::class';
    }
}

// Group seeders by category for better organization
$categorized = [
    'Core System' => [
        'InstitutionsSeeder::class',
        'BranchesSeeder::class',
        'DepartmentsSeeder::class',
        'DepartmentSeeder::class',
    ],
    'User and Role Management' => [
        'RolesSeeder::class',
        'RoleSeeder::class',
        'SubrolesSeeder::class',
        'SubRoleSeeder::class',
        'PermissionsSeeder::class',
        'PermissionSeeder::class',
        'UsersSeeder::class',
        'UserrolesSeeder::class',
        'UserRoleSeeder::class',
        'UserpermissionsSeeder::class',
        'SubrolepermissionsSeeder::class',
        'RolepermissionsSeeder::class',
        'UsersubmenusSeeder::class',
        'UsersubrolesSeeder::class',
        'UserprofilesSeeder::class',
        'UsersecurityprofilesSeeder::class',
        'PasswordpoliciesSeeder::class',
        'PasswordPolicySeeder::class',
    ],
    'Menu System' => [
        'MenuSeeder::class',
        'MenulistSeeder::class',
        'ConsolidatedMenuSeeder::class',
        'SubMenusSeeder::class',
        'MenuActionsSeeder::class',
        'RoleMenuActionsSeeder::class',
        'RoleMenuActionSeeder::class',
    ],
    'Member Management' => [
        'ClientsSeeder::class',
        'ExampleMembersSeeder::class',
        'MembercategoriesSeeder::class',
        'ClientdocumentsSeeder::class',
        'PendingregistrationsSeeder::class',
        'WebportalusersSeeder::class',
    ],
    'Financial Products and Services' => [
        'ServicesSeeder::class',
        'ServiceSeeder::class',
        'ServicesTableSeeder::class',
        'SubProductsSeeder::class',
        'LoanSubProductsSeeder::class',
        'ProducthaschargesSeeder::class',
        'ProducthasinsuranceSeeder::class',
        'SavingstypesSeeder::class',
        'DeposittypesSeeder::class',
    ],
    'Accounting and GL' => [
        'AccountsSeeder::class',
        'GLaccountsSeeder::class',
        'GeneralledgerSeeder::class',
        'SubaccountsSeeder::class',
        'AssetaccountsSeeder::class',
        'CapitalaccountsSeeder::class',
        'ExpenseaccountsSeeder::class',
        'IncomeaccountsSeeder::class',
        'LiabilityaccountsSeeder::class',
        'BudgetaccountsSeeder::class',
        'AccounthistoricalbalancesSeeder::class',
        'HistoricalbalancesSeeder::class',
        'SetupAccountsSeeder::class',
        'Institution1AccountsSeeder_2025_07_18_025945::class',
    ],
    'Loans' => [
        'LoansSeeder::class',
        'LoanssummarySeeder::class',
        'LoansschedulesSeeder::class',
        'LoansarreasSeeder::class',
        'LoansoriginatedSeeder::class',
        'LoanapprovalsSeeder::class',
        'LoanauditlogsSeeder::class',
        'LoanstagesSeeder::class',
        'CurrentloansstagesSeeder::class',
        'LoanprocessprogressSeeder::class',
        'LoanproductchargesSeeder::class',
        'LoanprovisionsettingsSeeder::class',
        'LoanimagesSeeder::class',
        'LoancollateralSeeder::class',
        'LoancollateralsSeeder::class',
        'LoanguarantorsSeeder::class',
        'GuarantorsSeeder::class',
        'CollateralsSeeder::class',
        'CollateralTypesSeeder::class',
        'MaincollateraltypesSeeder::class',
        'CustomcollateralsSeeder::class',
        'SampleLoanCollateralSeeder::class',
        'GrouploansSeeder::class',
        'MaendeleoloansSeeder::class',
        'SettledloansSeeder::class',
        'ShortlongtermloansSeeder::class',
        'ApproversofloansstagesSeeder::class',
    ],
    'Banking and Transactions' => [
        'BanksSeeder::class',
        'BankaccountsSeeder::class',
        'BanktransactionsSeeder::class',
        'BanktransfersSeeder::class',
        'BankstatementsstagingtableSeeder::class',
        'TransactionsSeeder::class',
        'TransactionauditlogsSeeder::class',
        'TransactionreconciliationsSeeder::class',
        'TransactionretrylogsSeeder::class',
        'TransactionreversalsSeeder::class',
        'ReconciledtransactionsSeeder::class',
        'ReconciliationstagingtableSeeder::class',
        'ImbanktransactionsSeeder::class',
        'GepgtransactionsSeeder::class',
        'InternaltransfersSeeder::class',
    ],
    'Cash Management' => [
        'TillsSeeder::class',
        'TilltransactionsSeeder::class',
        'TillreconciliationsSeeder::class',
        'TillCashManagementSeeder::class',
        'TellerendofdaypositionsSeeder::class',
        'TellersSeeder::class',
        'CashmovementsSeeder::class',
        'CashflowconfigurationsSeeder::class',
        'CashInTransitProvidersSeeder::class',
        'VaultsSeeder::class',
        'StrongroomledgersSeeder::class',
        'SecuritytransportlogsSeeder::class',
    ],
    'Payments and Billing' => [
        'PaymentmethodsSeeder::class',
        'PaymentsSeeder::class',
        'PaymentnotificationsSeeder::class',
        'BillsSeeder::class',
        'BillingcyclesSeeder::class',
        'OrdersSeeder::class',
        'ChequesSeeder::class',
        'ChequebooksSeeder::class',
    ],
    'Shares and Investments' => [
        'SharesSeeder::class',
        'ShareownershipSeeder::class',
        'SharetransfersSeeder::class',
        'SharewithdrawalsSeeder::class',
        'IssuedsharesSeeder::class',
        'InvestmentslistSeeder::class',
        'InvestmenttypesSeeder::class',
        'DividendsSeeder::class',
        'ContributionsSeeder::class',
    ],
    'Expenses and Budget' => [
        'ExpensesSeeder::class',
        'ExpenseapprovalsSeeder::class',
        'BudgetmanagementsSeeder::class',
        'BudgetapproversSeeder::class',
        'MainbudgetSeeder::class',
        'MainbudgetpendingSeeder::class',
    ],
    'HR and Employees' => [
        'EmployeesSeeder::class',
        'EmployeerolesSeeder::class',
        'EmployeerequestsSeeder::class',
        'EmployeefilesSeeder::class',
        'PayrollsSeeder::class',
        'LeavesSeeder::class',
        'LeavemanagementSeeder::class',
        'BenefitsSeeder::class',
        'HiresapprovalsSeeder::class',
        'InterviewsSeeder::class',
        'JobpostingsSeeder::class',
    ],
    'Approvals and Committees' => [
        'ApprovalsSeeder::class',
        'ApprovalactionsSeeder::class',
        'ApprovalcommentsSeeder::class',
        'ApprovalmatrixconfigsSeeder::class',
        'CommitteesSeeder::class',
        'CommitteeSeeder::class',
        'CommitteemembersSeeder::class',
        'CommitteemembershipsSeeder::class',
        'CommitteeapprovalsSeeder::class',
    ],
    'Complaints and Support' => [
        'ComplaintsSeeder::class',
        'ComplaintCategoriesSeeder::class',
        'ComplaintStatusesSeeder::class',
    ],
    'Reports and Analytics' => [
        'ReportsSeeder::class',
        'ScheduledreportsSeeder::class',
        'DatafeedsSeeder::class',
        'FinancialdataSeeder::class',
        'FinancialpositionSeeder::class',
        'FinancialratiosSeeder::class',
        'AnalysissessionsSeeder::class',
        'ScoresSeeder::class',
    ],
    'Insurance and Charges' => [
        'ChargesSeeder::class',
        'ChargeslistSeeder::class',
        'InsurancesSeeder::class',
        'InsurancelistSeeder::class',
        'InterestpayablesSeeder::class',
    ],
    'Assets and Inventory' => [
        'AssetslistSeeder::class',
        'AsseturlSeeder::class',
        'PpesSeeder::class',
        'PpePermissionsSeeder::class',
        'InventoriesSeeder::class',
        'LandedpropertytypesSeeder::class',
        'MovablepropertytypesSeeder::class',
    ],
    'Vendors and Contracts' => [
        'VendorsSeeder::class',
        'ContractmanagementsSeeder::class',
        'TendersSeeder::class',
        'PurchasesSeeder::class',
    ],
    'Notifications and Communication' => [
        'NotificationsSeeder::class',
        'NotificationlogsSeeder::class',
        'EmailsSeeder::class',
        'MandatorysavingsnotificationsSeeder::class',
        'QueryresponsesSeeder::class',
    ],
    'System and Audit' => [
        'AuditlogsSeeder::class',
        'UseractionlogsSeeder::class',
        'ApikeysSeeder::class',
        'ProcessCodeConfigsSeeder::class',
        'CallbackSeeder::class',
        'OnboardingSeeder::class',
        'AiinteractionsSeeder::class',
    ],
    'Locations' => [
        'RegionsSeeder::class',
        'DistrictsSeeder::class',
        'WardsSeeder::class',
    ],
    'Other Configurations' => [
        'CurrenciesSeeder::class',
        'DocumenttypesSeeder::class',
        'MobileNetworksSeeder::class',
        'MnosSeeder::class',
        'TaxesSeeder::class',
        'StandinginstructionsSeeder::class',
        'LockedamountsSeeder::class',
        'LossreservesSeeder::class',
        'MandatorySavingsSettingsSeeder::class',
        'MandatorysavingstrackingSeeder::class',
        'UnearneddeferredrevenueSeeder::class',
        'PayablesSeeder::class',
        'ReceivablesSeeder::class',
        'DashboardTableSeeder::class',
        'TemppermissionsSeeder::class',
    ],
    'Specific Account Seeders' => [
        'ADMINISTRATIVEEXPENSESSeeder::class',
        'ASSETRECEIVABLESSeeder::class',
        'AUDITANDCOMPLIANCESeeder::class',
        'CASHANDCASHEQUIVALENTSSeeder::class',
        'DEPRECIATIONSeeder::class',
        'GENERALRESERVESSeeder::class',
        'INCOMEACCOUNTRECEIVABLESeeder::class',
        'INCOMEFROMINVESTMENTSSeeder::class',
        'INCOMEFROMLOANSSeeder::class',
        'LIABILITYSUSPENSEACCOUNTSSeeder::class',
        'LOANPORTFOLIOSeeder::class',
        'MEMBERSSAVINGSANDDEPOSITSSeeder::class',
        'OTHERINCOMESeeder::class',
        'RETAINEDEARNINGSSeeder::class',
        'REVALUATIONRESERVESeeder::class',
        'SALARIESANDWAGESSeeder::class',
        'SHARECAPITALSeeder::class',
        'STATUTORYRESERVESSeeder::class',
        'TRADEANDOTHERPAYABLESSeeder::class',
    ],
    'Organization Structure' => [
        'SaccosOrganizationalStructureSeeder::class',
        'SaccosStructureSeeder::class',
        'GroupsSeeder::class',
        'MeetingsSeeder::class',
        'MeetingattendanceSeeder::class',
        'MeetingdocumentsSeeder::class',
        'LeadershipsSeeder::class',
        'ApplicantsSeeder::class',
        'InstitutionfilesSeeder::class',
        'ProjectSeeder::class',
        'EntriesSeeder::class',
        'EntriesamountSeeder::class',
    ],
];

// Build the new DatabaseSeeder content
$content = "<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \$this->call([
";

foreach ($categorized as $category => $categorySeeders) {
    $content .= "            // $category\n";
    foreach ($categorySeeders as $seeder) {
        // Check if this seeder exists in our files
        $seederClass = str_replace('::class', '', $seeder);
        $exists = false;
        foreach ($seeders as $availableSeeder) {
            if (str_replace('::class', '', $availableSeeder) === $seederClass) {
                $exists = true;
                break;
            }
        }
        if ($exists) {
            $content .= "            $seeder,\n";
        }
    }
    $content .= "\n";
}

$content .= "        ]);
    }
}
";

file_put_contents(__DIR__ . '/database/seeders/DatabaseSeeder.php', $content);

echo "DatabaseSeeder has been updated with all seeders!\n";
echo "Total seeders included: " . count($seeders) . "\n";