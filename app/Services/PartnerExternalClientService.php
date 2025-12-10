<?php

namespace App\Services;

use App\Models\PartnerExternalClient;
use App\Services\SubscriptionService;

class PartnerExternalClientService
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Add external client for partner.
     */
    public function addExternalClient($partnerId, $clientData)
    {
        // Check client limit
        if (!$this->subscriptionService->canAddMoreClients($partnerId)) {
            throw new \Exception('Client limit reached for current subscription plan');
        }

        return PartnerExternalClient::create([
            'partner_company_id' => $partnerId,
            'client_name' => $clientData['client_name'],
            'contact_person' => $clientData['contact_person'] ?? null,
            'email' => $clientData['email'] ?? null,
            'phone' => $clientData['phone'] ?? null,
            'industry' => $clientData['industry'] ?? null,
            'sector' => $clientData['sector'] ?? null,
            'address' => $clientData['address'] ?? null,
            'city' => $clientData['city'] ?? null,
            'country' => $clientData['country'] ?? null,
            'status' => 'active',
            'notes' => $clientData['notes'] ?? null,
            'custom_fields' => $clientData['custom_fields'] ?? null,
        ]);
    }

    /**
     * Update external client.
     */
    public function updateExternalClient($clientId, $clientData)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        
        $client->update([
            'client_name' => $clientData['client_name'] ?? $client->client_name,
            'contact_person' => $clientData['contact_person'] ?? $client->contact_person,
            'email' => $clientData['email'] ?? $client->email,
            'phone' => $clientData['phone'] ?? $client->phone,
            'industry' => $clientData['industry'] ?? $client->industry,
            'sector' => $clientData['sector'] ?? $client->sector,
            'address' => $clientData['address'] ?? $client->address,
            'city' => $clientData['city'] ?? $client->city,
            'country' => $clientData['country'] ?? $client->country,
            'status' => $clientData['status'] ?? $client->status,
            'notes' => $clientData['notes'] ?? $client->notes,
            'custom_fields' => $clientData['custom_fields'] ?? $client->custom_fields,
        ]);

        return $client;
    }

    /**
     * Delete external client.
     */
    public function deleteExternalClient($clientId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        return $client->delete();
    }

    /**
     * Get all external clients for partner.
     */
    public function getExternalClients($partnerId)
    {
        return PartnerExternalClient::where('partner_company_id', $partnerId)
            ->with(['locations', 'documents', 'reports'])
            ->get();
    }

    /**
     * Check if partner can add more clients.
     */
    public function canAddMoreClients($partnerId)
    {
        return $this->subscriptionService->canAddMoreClients($partnerId);
    }

    /**
     * Get client limit for partner.
     */
    public function getClientLimit($partnerId)
    {
        return $this->subscriptionService->getClientLimit($partnerId);
    }

    /**
     * Get current client count for partner.
     */
    public function getClientCount($partnerId)
    {
        return $this->subscriptionService->getClientCount($partnerId);
    }
}

