<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_pdf(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->create(['employee_id' => $employee->id]);

        $response = $this->actingAs($user)->get(route('leave-requests.download-pdf', $leaveRequest));

        $response->assertStatus(200);
    }

    public function test_manager_can_download_pdf(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::MANAGER]);

        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id, 'role' => EmployeeRole::EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->create(['employee_id' => $otherEmployee->id]);

        $response = $this->actingAs($user)->get(route('leave-requests.download-pdf', $leaveRequest));

        $response->assertStatus(200);
    }

    public function test_hrd_can_download_pdf(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::HRD]);

        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id, 'role' => EmployeeRole::EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->create(['employee_id' => $otherEmployee->id]);

        $response = $this->actingAs($user)->get(route('leave-requests.download-pdf', $leaveRequest));

        $response->assertStatus(200);
    }

    public function test_admin_can_download_pdf(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::ADMIN]);

        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id, 'role' => EmployeeRole::EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->create(['employee_id' => $otherEmployee->id]);

        $response = $this->actingAs($user)->get(route('leave-requests.download-pdf', $leaveRequest));

        $response->assertStatus(200);
    }

    public function test_other_employee_cannot_download_pdf(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::EMPLOYEE]);

        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id, 'role' => EmployeeRole::EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->create(['employee_id' => $otherEmployee->id]);

        $response = $this->actingAs($user)->get(route('leave-requests.download-pdf', $leaveRequest));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_attachment(): void
    {
        Storage::fake('public');
        $path = 'attachments/test.pdf';
        Storage::put($path, 'test content');

        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::ADMIN]);

        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id, 'role' => EmployeeRole::EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $otherEmployee->id,
            'attachment_path' => $path,
        ]);

        $response = $this->actingAs($user)->get(route('leave-requests.attachment.view', $leaveRequest));

        $response->assertStatus(200);
    }

    public function test_other_employee_cannot_view_attachment(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::EMPLOYEE]);

        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id, 'role' => EmployeeRole::EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->create(['employee_id' => $otherEmployee->id]);

        $response = $this->actingAs($user)->get(route('leave-requests.attachment.view', $leaveRequest));

        $response->assertStatus(403);
    }
}
