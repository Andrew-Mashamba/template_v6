<!-- Share Statement Modal -->
<div class="modal fade @if($showShareStatement) show @endif" 
     style="display: {{ $showShareStatement ? 'block' : 'none' }};" 
     tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice"></i> Share Statement
                </h5>
                <button type="button" class="close text-white" wire:click="hideShareStatementModal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <!-- Statement Generation Form -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Member Number</label>
                                    <input type="text" class="form-control" wire:model="client_number" 
                                           placeholder="Enter member number">
                                    @error('client_number') 
                                        <span class="text-danger">{{ $message }}</span> 
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>From Date</label>
                                    <input type="date" class="form-control" wire:model="dateFrom">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>To Date</label>
                                    <input type="date" class="form-control" wire:model="dateTo">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-primary btn-block" 
                                            wire:click="generateShareStatement({{ $memberDetails->id ?? 'null' }}, '{{ $dateFrom }}', '{{ $dateTo }}')">
                                        <i class="fas fa-search"></i> Generate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statement Display -->
                @if($shareStatementData)
                <div class="card">
                    <div class="card-header bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="mb-0">
                                    Statement for: <strong>{{ $shareStatementData['member']->first_name }} {{ $shareStatementData['member']->last_name }}</strong>
                                    ({{ $shareStatementData['member']->client_number }})
                                </h6>
                                <small class="text-muted">
                                    Period: {{ $shareStatementData['period_start']->format('d/m/Y') }} to {{ $shareStatementData['period_end']->format('d/m/Y') }}
                                </small>
                            </div>
                            <div class="col-md-4 text-right">
                                <button class="btn btn-sm btn-success" wire:click="exportShareStatementCSV({{ $shareStatementData['member']->id }})">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                                <button class="btn btn-sm btn-danger" wire:click="exportShareStatementPDF({{ $shareStatementData['member']->id }})">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                                <button class="btn btn-sm btn-info" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        @foreach($shareStatementData['statements'] as $statement)
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">
                                <strong>{{ $statement['product_name'] }}</strong> - Account: {{ $statement['account_number'] }}
                            </h6>
                            
                            <!-- Opening Balance -->
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <strong>Opening Balance:</strong> {{ number_format($statement['opening_balance']) }} shares
                                </div>
                                <div class="col-md-6 text-right">
                                    <strong>Value:</strong> TZS {{ number_format($statement['opening_value'], 2) }}
                                </div>
                            </div>
                            
                            <!-- Transactions Table -->
                            @if(count($statement['transactions']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Reference</th>
                                            <th>Description</th>
                                            <th class="text-right">Shares</th>
                                            <th class="text-right">Amount (TZS)</th>
                                            <th class="text-right">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($statement['transactions'] as $transaction)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</td>
                                            <td>
                                                @if($transaction->type == 'PURCHASE')
                                                    <span class="badge badge-success">Purchase</span>
                                                @elseif($transaction->type == 'REDEMPTION')
                                                    <span class="badge badge-danger">Redemption</span>
                                                @elseif($transaction->type == 'TRANSFER_IN')
                                                    <span class="badge badge-info">Transfer In</span>
                                                @elseif($transaction->type == 'TRANSFER_OUT')
                                                    <span class="badge badge-warning">Transfer Out</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $transaction->type }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $transaction->reference }}</td>
                                            <td>{{ $transaction->narration }}</td>
                                            <td class="text-right">
                                                @if(in_array($transaction->type, ['PURCHASE', 'TRANSFER_IN']))
                                                    <span class="text-success">+{{ number_format($transaction->shares) }}</span>
                                                @else
                                                    <span class="text-danger">-{{ number_format($transaction->shares) }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right">{{ number_format($transaction->amount, 2) }}</td>
                                            <td class="text-right">{{ number_format($transaction->balance_after) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No transactions found for this period.
                            </div>
                            @endif
                            
                            <!-- Summary -->
                            <div class="row mt-3 bg-light p-2">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td>Total Purchases:</td>
                                            <td class="text-right">{{ number_format($statement['total_purchases']) }} shares</td>
                                        </tr>
                                        <tr>
                                            <td>Total Redemptions:</td>
                                            <td class="text-right">{{ number_format($statement['total_redemptions']) }} shares</td>
                                        </tr>
                                        <tr>
                                            <td>Total Transfers In:</td>
                                            <td class="text-right">{{ number_format($statement['total_transfers_in']) }} shares</td>
                                        </tr>
                                        <tr>
                                            <td>Total Transfers Out:</td>
                                            <td class="text-right">{{ number_format($statement['total_transfers_out']) }} shares</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-success mb-0">
                                        <h6 class="mb-1"><strong>Closing Balance</strong></h6>
                                        <div>Shares: <strong>{{ number_format($statement['closing_balance']) }}</strong></div>
                                        <div>Value: <strong>TZS {{ number_format($statement['closing_value'], 2) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="card-footer text-muted">
                        <small>
                            Generated on: {{ $shareStatementData['generated_at']->format('d/m/Y H:i:s') }} by {{ auth()->user()->name }}
                        </small>
                    </div>
                </div>
                @endif
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="hideShareStatementModal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

@if($showShareStatement)
<div class="modal-backdrop fade show"></div>
@endif

<!-- Print Styles -->
<style>
@media print {
    .modal-header .close,
    .modal-footer,
    .btn {
        display: none !important;
    }
    
    .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
    }
    
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
    
    .modal-backdrop {
        display: none !important;
    }
}
</style>