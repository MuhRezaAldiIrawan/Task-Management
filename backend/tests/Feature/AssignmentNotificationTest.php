<?php

declare(strict_types=1);

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

it('lists available users for assignment', function () {
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);
    $userA = User::factory()->create(['email' => 'usera@example.com', 'role' => 'user']);
    $userB = User::factory()->create(['email' => 'userb@example.com', 'role' => 'user']);

    $token = JWTAuth::fromUser($admin);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->getJson('/api/auth/users');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');

    expect($response->json('data'))->toContain([
        'id' => $admin->id,
        'name' => $admin->name,
        'email' => $admin->email,
    ], [
        'id' => $userA->id,
        'name' => $userA->name,
        'email' => $userA->email,
    ], [
        'id' => $userB->id,
        'name' => $userB->name,
        'email' => $userB->email,
    ]);
});

it('creates an in-app notification when a task is assigned', function () {
    $creator = User::factory()->create(['email' => 'creator@example.com', 'role' => 'manager']);
    $assignee = User::factory()->create(['email' => 'assignee@example.com', 'role' => 'user']);

    $token = JWTAuth::fromUser($creator);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->postJson('/api/auth/tasks', [
        'title' => 'Review onboarding docs',
        'description' => 'Need a quick review before launch.',
        'status' => 'pending',
        'priority' => 'high',
        'assigned_user_id' => $assignee->id,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $assignee->id,
        'notifiable_type' => User::class,
    ]);
});
