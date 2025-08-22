# PowerShell script to update NBC Internal Fund Transfer configuration in .env file

$envContent = Get-Content .env -Raw

# Update NBC Internal Fund Transfer configurations
$envContent = $envContent -replace 'NBC_INTERNAL_FUND_TRANSFER_BASE_URL=', 'NBC_INTERNAL_FUND_TRANSFER_BASE_URL=http://cbpuat.intra.nbc.co.tz:6666/api/nbc-sg/internal_ft'
$envContent = $envContent -replace 'NBC_INTERNAL_FUND_TRANSFER_API_KEY=', 'NBC_INTERNAL_FUND_TRANSFER_API_KEY=b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55'
$envContent = $envContent -replace 'NBC_INTERNAL_FUND_TRANSFER_USERNAME=', 'NBC_INTERNAL_FUND_TRANSFER_USERNAME=saccosuser'
$envContent = $envContent -replace 'NBC_INTERNAL_FUND_TRANSFER_PASSWORD=', 'NBC_INTERNAL_FUND_TRANSFER_PASSWORD=saccospass'
$envContent = $envContent -replace 'NBC_INTERNAL_FUND_TRANSFER_SERVICE_NAME=', 'NBC_INTERNAL_FUND_TRANSFER_SERVICE_NAME=internal_ft_saccos'
$envContent = $envContent -replace 'NBC_INTERNAL_FUND_TRANSFER_CHANNEL_ID=', 'NBC_INTERNAL_FUND_TRANSFER_CHANNEL_ID=SACCOSNBC'

# Update Account Details Lookup configurations
$envContent = $envContent -replace 'ACCOUNT_DETAILS_BASE_URL=', 'ACCOUNT_DETAILS_BASE_URL=http://cbpuat.intra.nbc.co.tz:9004/api/v1/account-lookup'
$envContent = $envContent -replace 'ACCOUNT_DETAILS_API_KEY=', 'ACCOUNT_DETAILS_API_KEY=b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55'
$envContent = $envContent -replace 'ACCOUNT_DETAILS_CHANNEL_NAME=', 'ACCOUNT_DETAILS_CHANNEL_NAME=NBC_SACCOS'
$envContent = $envContent -replace 'ACCOUNT_DETAILS_CHANNEL_CODE=', 'ACCOUNT_DETAILS_CHANNEL_CODE=SACCOSNBC'

# Write the updated content back to .env file
$envContent | Set-Content .env

Write-Host "NBC Internal Fund Transfer configuration updated successfully!" 