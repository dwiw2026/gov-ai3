<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Run seeder in tests that need routing/agency data
beforeEach(function () {
    $this->artisan('db:seed');
});

test('root redirects to dashboard', function () {
    $response = $this->get('/');
    $response->assertRedirect('/dashboard');
});

test('dashboard loads successfully', function () {
    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('analytics api returns json', function () {
    $response = $this->getJson('/api/analytics/overview');
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'total_complaints', 'today_complaints', 'pending_review',
                 'in_progress', 'resolved_today', 'sla_breached',
                 'avg_ai_confidence', 'human_review_rate',
             ]);
});

test('complaint analysis endpoint uses fallback classifier', function () {
    $response = $this->postJson('/api/complaints/analyze', [
        'message'       => 'Ada kebakaran di sekitar pasar',
        'location_text' => 'Jl. Pasar Baru',
        'channel'       => 'web',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'category', 'priority', 'responsible_agency',
                 'confidence', 'reason', 'requires_human_review',
             ]);

    $response->assertJsonPath('category', 'kebakaran')
             ->assertJsonPath('responsible_agency', 'DAMKAR')
             ->assertJsonPath('priority', 'critical');
});

test('complaint submit creates ticket and returns ticket number', function () {
    $response = $this->postJson('/api/complaints/submit', [
        'message'       => 'Ada pengamen di Jalan A Yani mengganggu pengguna jalan',
        'location_text' => 'Jl. A. Yani',
        'channel'       => 'web',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'ticket_number', 'status', 'category', 'priority',
                 'agency', 'ai_confidence', 'sla_deadline',
             ]);

    $ticketNumber = $response->json('ticket_number');
    expect($ticketNumber)->toStartWith('CA-');

    // Verify status endpoint
    $statusResponse = $this->getJson("/api/complaints/{$ticketNumber}/status");
    $statusResponse->assertStatus(200)
                   ->assertJsonPath('ticket_number', $ticketNumber);
});
