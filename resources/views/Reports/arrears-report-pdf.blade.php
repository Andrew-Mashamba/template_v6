<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #34495e;
        }
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #ecf0f1;
            padding: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #34495e;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #ecf0f1;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-row {
            display: table-row;
        }
        .summary-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px;
            width: 50%;
            border-bottom: 1px solid #ecf0f1;
        }
        .summary-value {
            display: table-cell;
            padding: 5px;
            text-align: right;
            border-bottom: 1px solid #ecf0f1;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #bdc3c7;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
        }
        .highlight {
            background-color: #f39c12;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .danger {
            color: #e74c3c;
            font-weight: bold;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $company ?? 'SACCOS' }}</h1>
        <h2>{{ $reportName }}</h2>
        <p>{{ $reportDescription }}</p>
        <p><strong>Report Period:</strong> {{ $data['report_period'] }}</p>
        <p><strong>Generated:</strong> {{ $data['generated_at'] }} | <strong>By:</strong> {{ $data['generated_by'] }}</p>
    </div>

    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">Executive Summary</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-label">Total Loans</div>
                <div class="summary-value">{{ number_format($data['summary']['total_loans']) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Active Loans</div>
                <div class="summary-value">{{ number_format($data['summary']['active_loans']) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Loans in Arrears</div>
                <div class="summary-value" class="{{ $data['summary']['loans_in_arrears'] > 0 ? 'danger' : '' }}">
                    {{ number_format($data['summary']['loans_in_arrears']) }}
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Portfolio</div>
                <div class="summary-value">{{ number_format($data['summary']['total_portfolio'], 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Arrears</div>
                <div class="summary-value" class="danger">{{ number_format($data['summary']['total_arrears'], 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Portfolio at Risk (%)</div>
                <div class="summary-value">{{ number_format($data['summary']['portfolio_at_risk'], 2) }}%</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Collection Efficiency (%)</div>
                <div class="summary-value" class="{{ $data['summary']['collection_efficiency'] > 80 ? 'success' : 'danger' }}">
                    {{ number_format($data['summary']['collection_efficiency'], 2) }}%
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Average Arrears Age (Days)</div>
                <div class="summary-value">{{ number_format($data['summary']['average_arrears_age'], 1) }}</div>
            </div>
        </div>
    </div>

    <!-- Risk Summary (for daily report) -->
    @if(isset($data['details']['risk_summary']) && !empty($data['details']['risk_summary']))
    <div class="section">
        <div class="section-title">Risk Level Summary</div>
        <table>
            <thead>
                <tr>
                    <th>Risk Level</th>
                    <th style="text-align: right;">Count</th>
                    <th style="text-align: right;">Total Arrears</th>
                    <th style="text-align: right;">Total Principal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['risk_summary'] as $level => $summary)
                <tr>
                    <td class="{{ $level == 'Critical' ? 'danger' : ($level == 'High Risk' ? 'danger' : '') }}">
                        {{ $level }}
                    </td>
                    <td style="text-align: right;">{{ number_format($summary['count']) }}</td>
                    <td style="text-align: right;">{{ number_format($summary['total_arrears'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($summary['total_principal'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Top Defaulters (for daily report) -->
    @if(isset($data['details']['top_defaulters']) && !empty($data['details']['top_defaulters']))
    <div class="section">
        <div class="section-title">Top 10 Defaulters</div>
        <table style="font-size: 11px;">
            <thead>
                <tr>
                    <th>Loan Ref</th>
                    <th>Member Name</th>
                    <th>Phone</th>
                    <th style="text-align: right;">Principal</th>
                    <th style="text-align: right;">Arrears</th>
                    <th style="text-align: right;">Days</th>
                    <th>Risk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['top_defaulters'] as $loan)
                <tr>
                    <td>{{ $loan->loan_reference ?? 'N/A' }}</td>
                    <td>{{ $loan->member_name ?? 'Unknown' }}</td>
                    <td>{{ $loan->phone ?? 'N/A' }}</td>
                    <td style="text-align: right;">{{ number_format($loan->principle ?? 0, 2) }}</td>
                    <td style="text-align: right;" class="danger">{{ number_format($loan->arrears_amount ?? 0, 2) }}</td>
                    <td style="text-align: right;">{{ $loan->days_in_arrears ?? 0 }}</td>
                    <td class="{{ ($loan->risk_level ?? '') == 'Critical' ? 'danger' : '' }}">
                        {{ $loan->risk_level ?? 'Unknown' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Aging Analysis -->
    @if(isset($data['details']['aging_analysis']) && !empty($data['details']['aging_analysis']))
    <div class="section">
        <div class="section-title">Aging Analysis</div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align: right;">Count</th>
                    <th style="text-align: right;">Amount</th>
                    <th style="text-align: right;">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['aging_analysis'] as $aging)
                <tr>
                    <td>{{ $aging['category'] }}</td>
                    <td style="text-align: right;">{{ number_format($aging['count']) }}</td>
                    <td style="text-align: right;">{{ number_format($aging['amount'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($aging['percentage'], 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Detailed Aging (for aging report) -->
    @if(isset($data['details']['detailed_aging']) && !empty($data['details']['detailed_aging']))
    <div class="section">
        <div class="section-title">Detailed Aging Buckets</div>
        <table>
            <thead>
                <tr>
                    <th>Bucket</th>
                    <th style="text-align: right;">Loans</th>
                    <th style="text-align: right;">Principal</th>
                    <th style="text-align: right;">Arrears</th>
                    <th style="text-align: right;">Provision Rate</th>
                    <th style="text-align: right;">Provision Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['detailed_aging'] as $bucket)
                <tr>
                    <td>{{ $bucket['bucket'] }}</td>
                    <td style="text-align: right;">{{ number_format($bucket['loan_count']) }}</td>
                    <td style="text-align: right;">{{ number_format($bucket['principal_amount'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($bucket['arrears_amount'], 2) }}</td>
                    <td style="text-align: right;">{{ $bucket['provision_rate'] }}%</td>
                    <td style="text-align: right;">{{ number_format($bucket['provision_amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Product Performance (for monthly report) -->
    @if(isset($data['details']['product_performance']) && !empty($data['details']['product_performance']))
    <div class="section">
        <div class="section-title">Product Performance</div>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align: right;">Total Loans</th>
                    <th style="text-align: right;">Portfolio</th>
                    <th style="text-align: right;">Arrears</th>
                    <th style="text-align: right;">PAR %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['product_performance'] as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td style="text-align: right;">{{ number_format($product->total_loans) }}</td>
                    <td style="text-align: right;">{{ number_format($product->portfolio_amount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($product->arrears_amount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($product->par, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Branch Comparison (for monthly report) -->
    @if(isset($data['details']['branch_comparison']) && !empty($data['details']['branch_comparison']))
    <div class="section">
        <div class="section-title">Branch Performance</div>
        <table>
            <thead>
                <tr>
                    <th>Branch</th>
                    <th style="text-align: right;">Loans</th>
                    <th style="text-align: right;">Portfolio</th>
                    <th style="text-align: right;">Arrears</th>
                    <th style="text-align: right;">PAR %</th>
                    <th style="text-align: right;">NPL %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['branch_comparison'] as $branch)
                <tr>
                    <td>{{ $branch->branch_name }}</td>
                    <td style="text-align: right;">{{ number_format($branch->total_loans) }}</td>
                    <td style="text-align: right;">{{ number_format($branch->portfolio_amount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($branch->arrears_amount, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($branch->par, 2) }}%</td>
                    <td style="text-align: right;">{{ number_format($branch->npl_ratio, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Quarterly Breakdown (for annual report) -->
    @if(isset($data['details']['quarterly_breakdown']) && !empty($data['details']['quarterly_breakdown']))
    <div class="section">
        <div class="section-title">Quarterly Performance</div>
        <table>
            <thead>
                <tr>
                    <th>Quarter</th>
                    <th style="text-align: right;">Arrears Count</th>
                    <th style="text-align: right;">Arrears Amount</th>
                    <th style="text-align: right;">Portfolio</th>
                    <th style="text-align: right;">Collections</th>
                    <th style="text-align: right;">PAR %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['quarterly_breakdown'] as $quarter)
                <tr>
                    <td>{{ $quarter['quarter'] }}</td>
                    <td style="text-align: right;">{{ number_format($quarter['arrears_count']) }}</td>
                    <td style="text-align: right;">{{ number_format($quarter['arrears_amount'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($quarter['portfolio'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($quarter['collections'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($quarter['par'], 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Recovery Summary (for recovery status report) -->
    @if(isset($data['details']['recovery_summary']) && !empty($data['details']['recovery_summary']))
    <div class="section">
        <div class="section-title">Recovery Summary by Stage</div>
        <table>
            <thead>
                <tr>
                    <th>Recovery Stage</th>
                    <th style="text-align: right;">Count</th>
                    <th style="text-align: right;">Total Arrears</th>
                    <th style="text-align: right;">Total Recovered</th>
                    <th style="text-align: right;">Recovery Rate %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['details']['recovery_summary'] as $stage)
                <tr>
                    <td>{{ $stage['stage'] }}</td>
                    <td style="text-align: right;">{{ number_format($stage['count']) }}</td>
                    <td style="text-align: right;">{{ number_format($stage['total_arrears'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($stage['total_recovered'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($stage['recovery_rate'], 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Detailed Arrears List (for daily report) -->
    @if(isset($data['details']['arrears_list']) && !empty($data['details']['arrears_list']))
    <div class="section" style="page-break-before: auto;">
        <div class="section-title">Detailed Arrears List</div>
        <table style="font-size: 10px;">
            <thead>
                <tr>
                    <th>Loan Ref</th>
                    <th>Client #</th>
                    <th>Member Name</th>
                    <th>Phone</th>
                    <th>Branch</th>
                    <th>Product</th>
                    <th style="text-align: right;">Principal</th>
                    <th style="text-align: right;">Installment</th>
                    <th style="text-align: right;">Paid</th>
                    <th style="text-align: right;">Arrears</th>
                    <th style="text-align: right;">Days</th>
                    <th>Risk</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $displayLimit = 50; // Limit for PDF to avoid too many pages
                    $counter = 0;
                @endphp
                @foreach($data['details']['arrears_list'] as $loan)
                    @if($counter < $displayLimit)
                    <tr>
                        <td>{{ $loan->loan_reference ?? 'N/A' }}</td>
                        <td>{{ $loan->client_number ?? 'N/A' }}</td>
                        <td>{{ $loan->member_name ?? 'Unknown' }}</td>
                        <td>{{ $loan->phone ?? 'N/A' }}</td>
                        <td>{{ $loan->branch_name ?? 'N/A' }}</td>
                        <td>{{ $loan->product_name ?? 'N/A' }}</td>
                        <td style="text-align: right;">{{ number_format($loan->principle ?? 0, 2) }}</td>
                        <td style="text-align: right;">{{ number_format($loan->installment ?? 0, 2) }}</td>
                        <td style="text-align: right;">{{ number_format($loan->payment ?? 0, 2) }}</td>
                        <td style="text-align: right;" class="{{ $loan->days_in_arrears > 60 ? 'danger' : '' }}">
                            {{ number_format($loan->arrears_amount ?? 0, 2) }}
                        </td>
                        <td style="text-align: right;">{{ $loan->days_in_arrears ?? 0 }}</td>
                        <td class="{{ ($loan->risk_level ?? '') == 'Critical' || ($loan->risk_level ?? '') == 'High Risk' ? 'danger' : '' }}">
                            {{ $loan->risk_level ?? 'Unknown' }}
                        </td>
                    </tr>
                    @php $counter++; @endphp
                    @endif
                @endforeach
            </tbody>
            @if(count($data['details']['arrears_list']) > $displayLimit)
            <tfoot>
                <tr>
                    <td colspan="12" style="text-align: center; font-style: italic; padding: 10px;">
                        ... and {{ count($data['details']['arrears_list']) - $displayLimit }} more records
                        (showing top {{ $displayLimit }} by days in arrears)
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Summary Statistics -->
    @if(isset($data['details']['total_loans_in_arrears']))
    <div class="section">
        <div class="section-title">Summary Statistics</div>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-label">Total Loans in Arrears</div>
                <div class="summary-value">{{ number_format($data['details']['total_loans_in_arrears'] ?? 0) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Arrears Amount</div>
                <div class="summary-value danger">{{ number_format($data['details']['total_arrears_amount'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Average Days in Arrears</div>
                <div class="summary-value">{{ number_format($data['details']['average_days_in_arrears'] ?? 0, 1) }} days</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">New Arrears Today</div>
                <div class="summary-value">{{ number_format($data['details']['new_arrears_today'] ?? 0) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Collections Today</div>
                <div class="summary-value success">{{ number_format($data['details']['collections_today'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This report is system generated and confidential.</p>
        <p>© {{ date('Y') }} {{ $company ?? 'SACCOS' }} - All Rights Reserved</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>