<?php

namespace App\Services\NbcPayments;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class LukuService
{
    protected string $endpoint = 'https://uat-strategic-luku.com/api'; // Update with production when ready
    protected string $token = 'YOUR_SECURELY_STORED_TOKEN';

    public function lookup(string $meterNumber, string $accountNumber): array
    {
        $payload = <<<XML
<GepgGateway>
    <GepgGatewayBillQryReq>
        <GepgGatewayHdr>
            <ChannelID>CBP</ChannelID>
            <ChannelName>CD</ChannelName>
            <Service>LUKU_INQ</Service>
        </GepgGatewayHdr>
        <gepgBillQryReq>
            <ChannelRef>CBP1234</ChannelRef>
            <CustCtrNum>{$meterNumber}</CustCtrNum>
            <DebitAccountNo>{$accountNumber}</DebitAccountNo>
            <DebitAccountCurrency>TZS</DebitAccountCurrency>
        </gepgBillQryReq>
    </GepgGatewayBillQryReq>
    <gepggatewaySignature>SECURE_SIGNATURE</gepggatewaySignature>
</GepgGateway>
XML;

        try {
            Log::info('Initiating LUKU lookup request', ['payload' => $payload]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
                'NBC-Authorization' => "Bearer {$this->token}",
            ])->post("{$this->endpoint}/lookup", $payload);

            Log::info('LUKU lookup response received', ['status' => $response->status(), 'body' => $response->body()]);

            return $this->parseXmlResponse($response->body());

        } catch (Exception $e) {
            Log::error('Error during LUKU lookup', ['error' => $e->getMessage()]);
            return ['error' => 'Failed to perform lookup.'];
        }
    }

    public function pay(array $data): array
    {
        try {
            DB::beginTransaction();

            $transactionId = DB::table('transactions')->insertGetId([
                'institution_id' => auth()->user()->institution_id ?? 1,
                'branch_id' => auth()->user()->branch_id ?? 1,
                'service_name' => 'LUKU',
                'service_code' => 'LUKU_PAY',
                'action_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'reference_number' => $data['channel_ref'],
                'description' => 'LUKU Payment Request',
                'third_party_reference' => $data['cbp_gw_ref'],
                'status' => 'pending',
                'transaction_type' => 'payment',
                'currency' => 'TZS',
                'created_by' => auth()->id() ?? 1,
                'bank' => 'NBC',
                'bank_account' => $data['account_number'],
                'mirror_account' => $data['credit_account'] ?? null,
                'client_bank_account' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('LUKU transaction recorded in DB', ['transaction_id' => $transactionId]);

            $payload = <<<XML
<GepgGateway>
    <GepgGatewayVendReq>
        <GepgGatewayHdr>
            <ChannelID>CBP</ChannelID>
            <ChannelName>CD</ChannelName>
            <Service>LUKU_PAY</Service>
        </GepgGatewayHdr>
        <PmtHdr>
            <ChannelRef>{$data['channel_ref']}</ChannelRef>
            <CbpGwRef>{$data['cbp_gw_ref']}</CbpGwRef>
            <StsCode>7101</StsCode>
            <ResultUrl>{$data['result_url']}</ResultUrl>
        </PmtHdr>
        <gepgVendReqInf>
            <ChannelTrxId>{$data['transaction_id']}</ChannelTrxId>
            <CustCtrNum>{$data['meter_number']}</CustCtrNum>
            <DebitAccountNo>{$data['account_number']}</DebitAccountNo>
            <DebitAccountCurrency>TZS</DebitAccountCurrency>
            <Amount>{$data['amount']}</Amount>
        </gepgVendReqInf>
    </GepgGatewayVendReq>
    <gepggatewaySignature>SECURE_SIGNATURE</gepggatewaySignature>
</GepgGateway>
XML;

            Log::info('Sending LUKU payment request', ['payload' => $payload]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
                'NBC-Authorization' => "Bearer {$this->token}",
            ])->post("{$this->endpoint}/pay", $payload);

            Log::info('LUKU payment response received', ['status' => $response->status(), 'body' => $response->body()]);

            DB::commit();

            return $this->parseXmlResponse($response->body());

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('LUKU payment failed', ['error' => $e->getMessage(), 'data' => $data]);
            return ['error' => 'Payment failed due to a system error.'];
        }
    }

    private function parseXmlResponse(string $xml): array
    {
        try {
            $object = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
            if ($object === false) {
                throw new Exception('Failed to parse XML.');
            }
            return json_decode(json_encode($object), true);
        } catch (Exception $e) {
            Log::error('XML parsing failed', ['error' => $e->getMessage(), 'xml' => $xml]);
            return ['error' => 'Invalid XML response received.'];
        }
    }
}
