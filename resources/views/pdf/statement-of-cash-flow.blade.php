<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement of Cash Flow - {{ $institution->name ?? 'NBC SACCO' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        .institution-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        .period-info {
            font-size: 14px;
            color: #6b7280;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .summary-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-change {
            font-size: 11px;
            margin-top: 5px;
        }
        .positive { color: #059669; }
        .negative { color: #dc2626; }
        .neutral { color: #6b7280; }
        .cash-flow-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .cash-flow-table th {
            background: #1e40af;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
        }
        .cash-flow-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        .cash-flow-table tr:nth-child(even) {
            background: #f9fafb;
        }
        .section-header {
            background: #f3f4f6 !important;
            font-weight: bold;
            color: #374151;
        }
        .total-row {
            background: #dbeafe !important;
            font-weight: bold;
            color: #1e40af;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        .positive-amount { color: #059669; }
        .negative-amount { color: #dc2626; }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .comparison-table th {
            background: #374151;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .comparison-table td {
            padding: 10px;
            border: 1px solid #e5e7eb;
            text-align: center;
            font-size: 11px;
        }
        .comparison-table .period-header {
            background: #6b7280;
            color: white;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .compliance-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
        }
        .compliance-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 10px;
        }
        .compliance-item {
            margin-bottom: 5px;
            font-size: 10px;
        }
        .generated-info {
            text-align: right;
        }
        .page-break {
            page-break-before: always;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="institution-name">{{ $institution->name ?? 'NBC SACCO' }}</div>
        <div class="report-title">Statement of Cash Flow</div>
        <div class="period-info">
            For the period ending {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('F j, Y') : 'Current Period' }}
        </div>
    </div>

    @if(isset($cashFlowData) && !empty($cashFlowData))
        <!-- Summary Grid -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Net Cash from Operations</div>
                <div class="summary-value">
                    {{ number_format($operatingCashFlow ?? 0, 2) }}
                </div>
                @if(isset($operatingCashFlowChange))
                    <div class="summary-change {{ $operatingCashFlowChange >= 0 ? 'positive' : 'negative' }}">
                        {{ $operatingCashFlowChange >= 0 ? '+' : '' }}{{ number_format($operatingCashFlowChange, 1) }}%
                    </div>
                @endif
            </div>
            <div class="summary-card">
                <div class="summary-label">Net Cash from Investing</div>
                <div class="summary-value">
                    {{ number_format($investingCashFlow ?? 0, 2) }}
                </div>
                @if(isset($investingCashFlowChange))
                    <div class="summary-change {{ $investingCashFlowChange >= 0 ? 'positive' : 'negative' }}">
                        {{ $investingCashFlowChange >= 0 ? '+' : '' }}{{ number_format($investingCashFlowChange, 1) }}%
                    </div>
                @endif
            </div>
            <div class="summary-card">
                <div class="summary-label">Net Cash from Financing</div>
                <div class="summary-value">
                    {{ number_format($financingCashFlow ?? 0, 2) }}
                </div>
                @if(isset($financingCashFlowChange))
                    <div class="summary-change {{ $financingCashFlowChange >= 0 ? 'positive' : 'negative' }}">
                        {{ $financingCashFlowChange >= 0 ? '+' : '' }}{{ number_format($financingCashFlowChange, 1) }}%
                    </div>
                @endif
            </div>
        </div>

        <!-- Main Cash Flow Statement -->
        <table class="cash-flow-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Amount ({{ $currency ?? 'TZS' }})</th>
                </tr>
            </thead>
            <tbody>
                <!-- Operating Activities -->
                <tr class="section-header">
                    <td colspan="2">CASH FLOWS FROM OPERATING ACTIVITIES</td>
                </tr>
                
                @if(isset($operatingActivities) && !empty($operatingActivities))
                    @foreach($operatingActivities as $item)
                        <tr>
                            <td>{{ is_array($item) ? ($item['description'] ?? 'N/A') : ($item->description ?? 'N/A') }}</td>
                            <td class="amount {{ (is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0)) >= 0 ? 'positive-amount' : 'negative-amount' }}">
                                {{ number_format(is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0), 2) }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>Net Income</td>
                        <td class="amount">{{ number_format($netIncome ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Depreciation & Amortization</td>
                        <td class="amount">{{ number_format($depreciation ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Changes in Working Capital</td>
                        <td class="amount">{{ number_format($workingCapitalChange ?? 0, 2) }}</td>
                    </tr>
                @endif
                
                <tr class="total-row">
                    <td><strong>Net Cash from Operating Activities</strong></td>
                    <td class="amount"><strong>{{ number_format($operatingCashFlow ?? 0, 2) }}</strong></td>
                </tr>

                <!-- Investing Activities -->
                <tr class="section-header">
                    <td colspan="2">CASH FLOWS FROM INVESTING ACTIVITIES</td>
                </tr>
                
                @if(isset($investingActivities) && !empty($investingActivities))
                    @foreach($investingActivities as $item)
                        <tr>
                            <td>{{ is_array($item) ? ($item['description'] ?? 'N/A') : ($item->description ?? 'N/A') }}</td>
                            <td class="amount {{ (is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0)) >= 0 ? 'positive-amount' : 'negative-amount' }}">
                                {{ number_format(is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0), 2) }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>Purchase of Fixed Assets</td>
                        <td class="amount negative-amount">{{ number_format($fixedAssetPurchase ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Sale of Fixed Assets</td>
                        <td class="amount positive-amount">{{ number_format($fixedAssetSale ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Investment in Securities</td>
                        <td class="amount negative-amount">{{ number_format($investmentPurchase ?? 0, 2) }}</td>
                    </tr>
                @endif
                
                <tr class="total-row">
                    <td><strong>Net Cash from Investing Activities</strong></td>
                    <td class="amount"><strong>{{ number_format($investingCashFlow ?? 0, 2) }}</strong></td>
                </tr>

                <!-- Financing Activities -->
                <tr class="section-header">
                    <td colspan="2">CASH FLOWS FROM FINANCING ACTIVITIES</td>
                </tr>
                
                @if(isset($financingActivities) && !empty($financingActivities))
                    @foreach($financingActivities as $item)
                        <tr>
                            <td>{{ is_array($item) ? ($item['description'] ?? 'N/A') : ($item->description ?? 'N/A') }}</td>
                            <td class="amount {{ (is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0)) >= 0 ? 'positive-amount' : 'negative-amount' }}">
                                {{ number_format(is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0), 2) }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>Member Deposits</td>
                        <td class="amount positive-amount">{{ number_format($memberDeposits ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Loan Disbursements</td>
                        <td class="amount negative-amount">{{ number_format($loanDisbursements ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Loan Repayments</td>
                        <td class="amount positive-amount">{{ number_format($loanRepayments ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Share Capital</td>
                        <td class="amount positive-amount">{{ number_format($shareCapital ?? 0, 2) }}</td>
                    </tr>
                @endif
                
                <tr class="total-row">
                    <td><strong>Net Cash from Financing Activities</strong></td>
                    <td class="amount"><strong>{{ number_format($financingCashFlow ?? 0, 2) }}</strong></td>
                </tr>

                <!-- Net Change and Ending Balance -->
                <tr class="section-header">
                    <td colspan="2">NET CHANGE IN CASH</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Net Increase (Decrease) in Cash</strong></td>
                    <td class="amount"><strong>{{ number_format($netCashChange ?? 0, 2) }}</strong></td>
                </tr>
                <tr>
                    <td>Cash at Beginning of Period</td>
                    <td class="amount">{{ number_format($beginningCash ?? 0, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Cash at End of Period</strong></td>
                    <td class="amount"><strong>{{ number_format($endingCash ?? 0, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Period Comparison -->
        @if(isset($periodComparison) && !empty($periodComparison))
            <div class="page-break"></div>
            <h3>Period Comparison</h3>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th class="period-header">Activity</th>
                        @foreach($periodComparison['periods'] ?? [] as $period)
                            <th class="period-header">{{ $period }}</th>
                        @endforeach
                        <th class="period-header">Change (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($periodComparison['data'] ?? [] as $activity => $values)
                        <tr>
                            <td><strong>{{ $activity }}</strong></td>
                            @foreach($values['amounts'] ?? [] as $amount)
                                <td>{{ number_format($amount, 2) }}</td>
                            @endforeach
                            <td class="{{ ($values['change'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($values['change'] ?? 0, 1) }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <div class="no-data">
            <h3>No Cash Flow Data Available</h3>
            <p>Cash flow data is not available for the selected period.</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div class="footer-grid">
            <div class="compliance-info">
                <div class="compliance-title">Regulatory Compliance</div>
                <div class="compliance-item">• Prepared in accordance with IFRS 7</div>
                <div class="compliance-item">• Cash flows classified by operating, investing, and financing activities</div>
                <div class="compliance-item">• Direct method presentation for operating activities</div>
                <div class="compliance-item">• Non-cash transactions disclosed separately</div>
                <div class="compliance-item">• Foreign currency effects properly translated</div>
            </div>
            <div class="generated-info">
                <div><strong>Generated:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</div>
                <div><strong>User:</strong> {{ auth()->user()->name ?? 'System' }}</div>
                <div><strong>Report ID:</strong> CASH-{{ date('Ymd') }}-{{ str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) }}</div>
                <div><strong>Page:</strong> <span class="page"></span></div>
            </div>
        </div>
    </div>

    <script>
        // Add page numbers
        document.addEventListener('DOMContentLoaded', function() {
            const pages = document.querySelectorAll('.page');
            pages.forEach((page, index) => {
                page.textContent = (index + 1);
            });
        });
    </script>
</body>
</html> 