<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use App\Models\ModelRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Use fake storage for file uploads
        Storage::fake('public');
    }

    protected function actingAsMemberUser(): User
    {
        $user = User::factory()->create([
            'role' => 'member',
            'is_member' => 0,
        ]);
        $this->actingAs($user);
        return $user;
    }

    public function test_membership_application_validation_errors(): void
    {
        $this->actingAsMemberUser();

        // Missing required fields like full_name, phone_number, seminar_date, venue
        $response = $this->postJson('/api/member/membership-apply', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'phone_number', 'seminar_date', 'venue']);
    }

    public function test_member_can_submit_membership_application_with_files(): void
    {
        $user = $this->actingAsMemberUser();

        $payload = [
            'full_name' => 'Test User',
            'phone_number' => '09123456789',
            'address' => 'Sample Address',
            'date_of_birth' => '1995-05-21',
            'place_of_birth' => 'City, Province',
            'age' => 30,
            'civil_status' => 'single',
            'religion' => 'Catholic',
            'tin_number' => '123-456-789',
            'employer' => 'Company',
            'position' => 'Developer',
            'monthly_income' => 50000,
            'other_income' => 'Side Business',
            'seminar_date' => '2025-09-30',
            'venue' => 'SMPC Main Office, Sariaya, Quezon',
            'brgy_clearance' => UploadedFile::fake()->image('brgy.png'),
            'birth_cert' => UploadedFile::fake()->image('birth.png'),
            'certificate_of_employment' => UploadedFile::fake()->create('coe.pdf', 120, 'application/pdf'),
            'applicant_photo' => UploadedFile::fake()->image('photo.jpg'),
            'valid_id_front' => UploadedFile::fake()->image('id_front.jpg'),
            'valid_id_back' => UploadedFile::fake()->image('id_back.jpg'),
        ];

        $response = $this->postJson('/api/member/membership-apply', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.full_name', 'Test User')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('requests', [
            'full_name' => 'Test User',
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Ensure files stored
        $requestRecord = ModelRequest::first();
        $this->assertNotNull($requestRecord->brgy_clearance);
        $this->assertTrue(
            Storage::disk('public')->exists($requestRecord->brgy_clearance),
            'Expected stored file for brgy_clearance to exist.'
        );
    }

    public function test_admin_can_approve_membership_and_create_member_record(): void
    {
        // Create applicant user + application
        $applicant = User::factory()->create(['role' => 'member', 'is_member' => 0]);
        $this->actingAs($applicant);

        $applyPayload = [
            'full_name' => 'Applicant X',
            'phone_number' => '09111111111',
            'seminar_date' => '2025-09-30',
            'venue' => 'Main Venue',
        ];
        $this->postJson('/api/member/membership-apply', $applyPayload)->assertCreated();
        $request = ModelRequest::first();

        // Create admin user to approve
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $approve = $this->putJson('/api/admin/requests/' . $request->id . '/status', [
            'status' => 'approved',
        ]);

        $approve->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('members', [
            'full_name' => 'Applicant X',
        ]);

        $applicant->refresh();
        $this->assertEquals(1, $applicant->is_member);
    }
}
