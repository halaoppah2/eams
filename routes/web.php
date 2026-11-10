<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EmployeeController;
use App\Controllers\ShiftController;
use App\Controllers\AttendanceController;
use App\Controllers\UserController;
use App\Controllers\LeaveController;
use App\Controllers\AuditController;
use App\Controllers\NotificationController;
use App\Controllers\ProfileController;
use App\Controllers\SignupController;
use App\Controllers\OrganizationStructureController;
use App\Controllers\OrganizationController;
use App\Controllers\RoleController;
use App\Controllers\HolidayController;
use App\Controllers\AttendanceCorrectionController;

// Home route
$router->get(
    '/',
    [AuthController::class, 'showLogin']
);

// Login routes
$router->post(
    '/login',
    [AuthController::class, 'login']
);

// Signup routes
$router->get(
    '/signup',
    [SignupController::class, 'show']
);

$router->post(
    '/signup',
    [SignupController::class, 'signup']
);

// Email verification routes
$router->get(
    '/verify-email',
    [SignupController::class, 'showVerification']
);

$router->post(
    '/verify-email',
    [SignupController::class, 'verifyEmail']
);

// Password reset routes
$router->get(
    '/forgot-password',
    [AuthController::class, 'showForgotPassword']
);

$router->post(
    '/forgot-password',
    [AuthController::class, 'requestPasswordReset']
);

$router->get(
    '/reset-password',
    [AuthController::class, 'showResetPassword']
);

$router->post(
    '/reset-password',
    [AuthController::class, 'resetPassword']
);

// Session activity route
$router->post(
    '/session/activity',
    [AuthController::class, 'activity'],
    ['auth']
);

// Logout route
$router->get(
    '/logout',
    [AuthController::class, 'logout'],
    ['auth']
);

// Dashboard route
$router->get(
    '/dashboard',
    [DashboardController::class, 'index'],
    ['auth', 'tenant']
);

// Organization management routes
$router->get(
    '/system/organizations',
    [OrganizationController::class, 'systemIndex'],
    ['auth', 'system_admin']
);

$router->post(
    '/system/organizations/{id}/status',
    [OrganizationController::class, 'setSystemStatus'],
    ['auth', 'system_admin']
);

$router->post(
    '/system/organizations/{id}/delete',
    [OrganizationController::class, 'deleteSystemOrganization'],
    ['auth', 'system_admin']
);

$router->get(
    '/settings/organization',
    [OrganizationController::class, 'ownerSettings'],
    ['auth', 'tenant']
);

$router->post(
    '/settings/organization/logo',
    [OrganizationController::class, 'uploadLogo'],
    ['auth', 'tenant']
);

$router->post(
    '/settings/organization/logo/delete',
    [OrganizationController::class, 'removeOwnLogo'],
    ['auth', 'tenant']
);

$router->post(
    '/settings/organization/details',
    [OrganizationController::class, 'updateOwnDetails'],
    ['auth', 'tenant']
);

$router->post(
    '/settings/organization/deactivate',
    [OrganizationController::class, 'deactivateOwnOrganization'],
    ['auth', 'tenant']
);

$router->post(
    '/settings/organization/delete',
    [OrganizationController::class, 'deleteOwnOrganization'],
    ['auth', 'tenant']
);

// Organization Structure routes
$router->get(
    '/settings/organization-structure',
    [OrganizationStructureController::class, 'index'],
    ['auth', 'tenant', 'permission:employee.update']
);

// Role and permission management routes
$router->get(
    '/roles',
    [RoleController::class, 'index'],
    ['auth', 'tenant', 'permission:role.view']
);

$router->get(
    '/roles/create',
    [RoleController::class, 'create'],
    ['auth', 'tenant', 'permission:role.create']
);

$router->post(
    '/roles',
    [RoleController::class, 'store'],
    ['auth', 'tenant', 'permission:role.create']
);

$router->get(
    '/roles/{id}/edit',
    [RoleController::class, 'edit'],
    ['auth', 'tenant', 'permission:role.update', 'permission:role.assign_permission']
);

$router->post(
    '/roles/{id}',
    [RoleController::class, 'update'],
    ['auth', 'tenant', 'permission:role.update', 'permission:role.assign_permission']
);

$router->post(
    '/roles/{id}/delete',
    [RoleController::class, 'delete'],
    ['auth', 'tenant', 'permission:role.delete']
);

$router->post(
    '/settings/organization-structure/{type}',
    [OrganizationStructureController::class, 'create'],
    ['auth', 'tenant', 'permission:employee.update']
);

$router->post(
    '/settings/organization-structure/{type}/{id}',
    [OrganizationStructureController::class, 'update'],
    ['auth', 'tenant', 'permission:employee.update']
);

$router->post(
    '/settings/organization-structure/{type}/{id}/status',
    [OrganizationStructureController::class, 'toggleStatus'],
    ['auth', 'tenant', 'permission:employee.update']
);

// Profile routes
$router->get(
    '/profile',
    [ProfileController::class, 'show'],
    ['auth']
);

$router->post(
    '/profile/picture',
    [ProfileController::class, 'updatePicture'],
    ['auth']
);

// Employee routes
$router->get(
    '/employees',
    [EmployeeController::class, 'index'],
    ['auth', 'permission:employee.view']
);

$router->get(
    '/employees/create',
    [EmployeeController::class, 'create'],
    ['auth', 'permission:employee.create']
);

$router->post(
    '/employees',
    [EmployeeController::class, 'store'],
    ['auth', 'permission:employee.create']
);

$router->get(
    '/employees/{id}',
    [EmployeeController::class, 'show'],
    ['auth', 'permission:employee.view']
);

$router->get(
    '/employees/{id}/edit',
    [EmployeeController::class, 'edit'],
    ['auth', 'permission:employee.update']
);

$router->post(
    '/employees/{id}/update',
    [EmployeeController::class, 'update'],
    ['auth', 'permission:employee.update']
);

$router->post(
    '/employees/{id}/delete',
    [EmployeeController::class, 'delete'],
    ['auth', 'permission:employee.delete']
);

// recycle bin routes for employees
$router->get(
    '/employees/recycle-bin',
    [EmployeeController::class, 'recycleBin'],
    ['auth', 'permission:employee.view']
);

$router->post(
    '/employees/{id}/restore',
    [EmployeeController::class, 'restore'],
    ['auth', 'permission:employee.restore']
);

$router->post(
    '/employees/{id}/permanent-delete',
    [EmployeeController::class, 'permanentlyDelete'],
    ['auth', 'permission:employee.delete_permanent']
);

// Shift routes
$router->get(
    '/shifts',
    [ShiftController::class, 'index'],
    ['auth', 'permission:shift.view']
);

$router->get(
    '/shifts/create',
    [ShiftController::class, 'create'],
    ['auth', 'permission:shift.create']
);

$router->post(
    '/shifts',
    [ShiftController::class, 'store'],
    ['auth', 'permission:shift.create']
);

$router->get(
    '/shifts/assignments',
    [ShiftController::class, 'assignments'],
    ['auth', 'permission:shift.update']
);

$router->post(
    '/shifts/assign',
    [ShiftController::class, 'assign'],
    ['auth', 'permission:shift.update']
);

$router->get(
    '/shifts/{id}/edit',
    [ShiftController::class, 'edit'],
    ['auth', 'permission:shift.update']
);

$router->post(
    '/shifts/{id}',
    [ShiftController::class, 'update'],
    ['auth', 'permission:shift.update']
);

$router->post(
    '/shifts/{id}/delete',
    [ShiftController::class, 'delete'],
    ['auth', 'permission:shift.delete']
);

// Attendance routes
$router->post(
    '/attendance/clock-in',
    [AttendanceController::class, 'clockIn'],
    ['auth']
);

$router->post(
    '/attendance/clock-out',
    [AttendanceController::class, 'clockOut'],
    ['auth']
);

$router->get(
    '/attendance',
    [AttendanceController::class, 'index'],
    ['auth']
);

// Attendance correction routes
$router->get(
    '/attendance/corrections',
    [AttendanceCorrectionController::class, 'index'],
    ['auth']
);

$router->get(
    '/attendance/{id}/correction-request',
    [AttendanceCorrectionController::class, 'requestForm'],
    ['auth', 'permission:attendance.correction.request']
);

$router->post(
    '/attendance/{id}/correction-request',
    [AttendanceCorrectionController::class, 'storeRequest'],
    ['auth', 'permission:attendance.correction.request']
);

$router->get(
    '/attendance/corrections/{id}',
    [AttendanceCorrectionController::class, 'reviewForm'],
    ['auth', 'permission:attendance.correction.view']
);

$router->post(
    '/attendance/corrections/{id}/approve',
    [AttendanceCorrectionController::class, 'approve'],
    ['auth', 'permission:attendance.correction.approve']
);

$router->post(
    '/attendance/corrections/{id}/reject',
    [AttendanceCorrectionController::class, 'reject'],
    ['auth', 'permission:attendance.correction.reject']
);

// Holiday routes
$router->get(
    '/holidays',
    [HolidayController::class, 'index'],
    ['auth', 'permission:holiday.view']
);

$router->get(
    '/holidays/create',
    [HolidayController::class, 'create'],
    ['auth', 'permission:holiday.create']
);

$router->post(
    '/holidays',
    [HolidayController::class, 'store'],
    ['auth', 'permission:holiday.create']
);

$router->get(
    '/holidays/{id}/edit',
    [HolidayController::class, 'edit'],
    ['auth', 'permission:holiday.update']
);

$router->post(
    '/holidays/{id}',
    [HolidayController::class, 'update'],
    ['auth', 'permission:holiday.update']
);

$router->post(
    '/holidays/{id}/delete',
    [HolidayController::class, 'delete'],
    ['auth', 'permission:holiday.delete']
);

// User management routes
$router->get(
    '/users/create',
    [UserController::class, 'create'],
    ['auth', 'permission:user.create']
);

$router->post(
    '/users',
    [UserController::class, 'store'],
    ['auth', 'permission:user.create']
);

$router->get(
    '/users',
    [UserController::class, 'index'],
    ['auth', 'permission:user.view']
);

// Leave routes
$router->get(
    '/leave',
    [LeaveController::class, 'index'],
    ['auth']
);

$router->get(
    '/leave/create',
    [LeaveController::class, 'create'],
    ['auth']
);

$router->post(
    '/leave',
    [LeaveController::class, 'store'],
    ['auth']
);

$router->get(
    '/leave/{id}',
    [LeaveController::class, 'show'],
    ['auth']
);

$router->post(
    '/leave/{id}/approve',
    [LeaveController::class, 'approve'],
    ['auth', 'permission:leave.approve']
);

$router->post(
    '/leave/{id}/reject',
    [LeaveController::class, 'reject'],
    ['auth', 'permission:leave.reject']
);

// Audit Routes
$router->get(
    '/audit',
    [AuditController::class, 'index'],
    ['auth']
);

$router->get(
    '/audit/{id}',
    [AuditController::class, 'show'],
    ['auth']
);

$router->get(
    '/audit/user/{userId}',
    [AuditController::class, 'byUser'],
    ['auth']
);

$router->get(
    '/audit/module/{module}',
    [AuditController::class, 'byModule'],
    ['auth']
);

$router->get(
    '/audit/entity/{entityType}/{entityId}',
    [AuditController::class, 'byEntity'],
    ['auth']
);

$router->post(
    '/audit/delete/{id}',
    [AuditController::class, 'delete'],
    ['auth']
);

$router->post(
    '/audit/empty',
    [AuditController::class, 'emptyAll'],
    ['auth']
);

// Notification Routes
$router->get(
    '/notifications',
    [NotificationController::class, 'index'],
    ['auth', 'permission:notification.view']
);

$router->get(
    '/notifications/create',
    [NotificationController::class, 'create'],
    ['auth', 'permission:notification.manage']
);

$router->post(
    '/notifications',
    [NotificationController::class, 'store'],
    ['auth', 'permission:notification.manage']
);

$router->get(
    '/notifications/{id}',
    [NotificationController::class, 'show'],
    ['auth', 'permission:notification.view']
);

$router->post(
    '/notifications/{id}/read',
    [NotificationController::class, 'markAsRead'],
    ['auth', 'permission:notification.view']
);

$router->post(
    '/notifications/read-all',
    [NotificationController::class, 'markAllAsRead'],
    ['auth', 'permission:notification.view']
);

// Reports routes
$router->get(
    '/reports',
    [AttendanceController::class, 'report'],
    ['auth', 'permission:attendance.view']
);
