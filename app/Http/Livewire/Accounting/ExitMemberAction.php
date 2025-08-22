<?php

namespace App\Http\Livewire\Accounting;

use App\Models\ClientsModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExitMemberAction extends Component
{



    public function render()
    {
        return view('livewire.accounting.exit-member-action');
    }

    public function download(){
        $member_exit_document=ClientsModel::where('id',session()->get('viewMemberId_details'))->value('member_exit_document');
        $filePath = storage_path('app/public/' .$member_exit_document);
        return response()->download($filePath);

    }
}
