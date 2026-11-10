<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NotificationService;
use App\Repositories\EmployeeRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\LeaveRepository;
use App\Database;

class DashboardController extends Controller
{
    public function index(): string
    {
        $auth = new AuthService();
        $user = $auth->user();
        $notificationService = new NotificationService(
            new NotificationRepository()
        );
        $leaveRepository = new LeaveRepository();

        $organizationId = (int) (
            $user['organization_id']
            ?? $_SESSION['user']['organization_id']
            ?? 0
        );

        $employeeCount = 0;
        $onLeaveToday = 0;

        $attendanceSummary = [
            'present_today' => 0,
            'late_today' => 0,
            'currently_working' => 0
        ];

        $attendanceTrend = [];

        if ($organizationId > 0) {
            $onLeaveToday = $leaveRepository->countEmployeesOnLeave(
                $organizationId,
                date('Y-m-d')
            );

            $employeeRepository = new EmployeeRepository();

            $employeeCount = $employeeRepository
                ->countByOrganization($organizationId);

            $attendanceRepository = new AttendanceRepository(
                Database::connection()
            );

            $attendanceSummary = $attendanceRepository
                ->getDashboardSummary(
                    $organizationId,
                    date('Y-m-d')
                );

            $startDate = date('Y-m-d', strtotime('-6 days'));
            $endDate = date('Y-m-d');

            $attendanceTrend = $attendanceRepository
                ->getAttendanceTrend(
                    $organizationId,
                    $startDate,
                    $endDate
                );
        }

        return $this->view('dashboard/index', [
            'user' => $user,
            'employeeCount' => $employeeCount,
            'attendanceSummary' => $attendanceSummary,
            'presentToday' => (int) (
                $attendanceSummary['present_today'] ?? 0
            ),
            'lateToday' => (int) (
                $attendanceSummary['late_today'] ?? 0
            ),
            'currentlyWorking' => (int) (
                $attendanceSummary['currently_working'] ?? 0
            ),
            'onLeaveToday' => $onLeaveToday,
            'notificationCount' => $notificationService
                ->countForCurrentUser(),
            'attendanceTrend' => $attendanceTrend
        ]);
    }
}
