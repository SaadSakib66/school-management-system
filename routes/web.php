<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SuperAdmin\GlobalDashboardController;
use App\Http\Controllers\SuperAdmin\SchoolsController;
use App\Http\Controllers\SuperAdmin\SchoolSwitchController;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ClassSubjectController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssignClassTeacherController;
use App\Http\Controllers\ClassTimetableController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\MarksRegisterController;
use App\Http\Controllers\MarksGradeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CommunicateController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\HomeworkReportController;
use App\Http\Controllers\LandingpageController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\FeeInvoiceController;
use App\Http\Controllers\Admin\FeeReportController;
use App\Http\Controllers\ParentPanel\ChildFeesController;
use App\Http\Controllers\Student\MyFeesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
// Shortcut to admin login
Route::redirect('/admin', '/admin/login')->name('admin.root');

Route::get('/', [LandingpageController::class, 'index'])->name('landing');

/*
|--------------------------------------------------------------------------
| Admin Public (auth)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('login',  [AdminController::class, 'loginPage'])->name('admin.login.page');
    Route::post('login', [AdminController::class, 'login'])->name('admin.login');
    Route::get('logout', [AdminController::class, 'logout'])->name('admin.logout');

    Route::get('forgot-password', [AdminController::class, 'forgotPassword'])->name('admin.forgotPassword');
});

/*
|--------------------------------------------------------------------------
| Super Admin
|--------------------------------------------------------------------------
*/
Route::prefix('superadmin')->middleware(['auth', 'super_admin'])->group(function () {
    Route::get('dashboard', [GlobalDashboardController::class, 'index'])->name('superadmin.dashboard');

    // My account
    Route::get('account',         [UserController::class, 'myAccount'])->name('superadmin.account');
    Route::get('edit_account',    [UserController::class, 'editMyAccount'])->name('superadmin.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccountSuperAdmin'])->name('superadmin.update-account');

    // Schools CRUD
    Route::get('schools',                    [SchoolsController::class, 'index'])->name('superadmin.schools.index');
    Route::get('schools/create',             [SchoolsController::class, 'create'])->name('superadmin.schools.create');
    Route::post('schools',                   [SchoolsController::class, 'store'])->name('superadmin.schools.store');
    Route::get('schools/{school}/edit',      [SchoolsController::class, 'edit'])->name('superadmin.schools.edit');
    Route::put('schools/{school}',           [SchoolsController::class, 'update'])->name('superadmin.schools.update');
    Route::delete('schools/{school}',        [SchoolsController::class, 'destroy'])->name('superadmin.schools.destroy');
    Route::patch('schools/{school}/status',  [SchoolsController::class, 'toggleStatus'])->name('superadmin.schools.toggle');

    // School context switcher
    Route::get('schools/switch',  [SchoolSwitchController::class, 'index'])->name('superadmin.schools.switch');
    Route::post('schools/switch', [SchoolSwitchController::class, 'set'])->name('superadmin.schools.switch.set');
    Route::post('schools/clear',  [SchoolSwitchController::class, 'clear'])->name('superadmin.schools.switch.clear');
});

/*
|--------------------------------------------------------------------------
| Admin (school-scoped)
|--------------------------------------------------------------------------
|
| Middleware:
| - auth
| - admin_or_super_with_context  (lets admins in; super admins only if a school is selected)
| - school.active                (ensures current school is enabled)
|
*/
Route::prefix('admin')->middleware(['auth', 'admin_or_super_with_context', 'school.active'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');

    // Admin users
    Route::get('list',              [AdminController::class, 'list'])->name('admin.admin.list');
    Route::get('add',               [AdminController::class, 'add'])->name('admin.admin.add');
    Route::post('add-admin',        [AdminController::class, 'addAdmin'])->name('admin.admin.add-admin');
    Route::get('edit/{id}',         [AdminController::class, 'editAdmin'])->name('admin.admin.edit-admin');
    Route::post('update/{id}',      [AdminController::class, 'updateAdmin'])->name('admin.admin.update-admin');
    Route::post('delete',           [AdminController::class, 'deleteAdmin'])->name('admin.admin.delete-admin');

    // NEW: PDF download (dompdf)
    Route::get('admins/download/{id}/{slug?}', [AdminController::class, 'downloadAdmin'])->name('admin.admin.download');

    // Students
    Route::get('student/list',                 [StudentController::class, 'list'])->name('admin.student.list');
    Route::get('student/create',               [StudentController::class, 'add'])->name('admin.student.add');
    Route::post('student/add-student',         [StudentController::class, 'insert'])->name('admin.student.add-student');
    Route::get('student/edit/{id}',            [StudentController::class, 'edit'])->name('admin.student.edit-student');
    Route::post('student/update/{id}',         [StudentController::class, 'update'])->name('admin.student.update-student'); // ✅ fixed route name
    Route::post('student/delete',              [StudentController::class, 'delete'])->name('admin.student.delete-student');
    // NEW: PDF (tab title friendly with optional slug)
    Route::get('students/download/{id}/{slug?}', [StudentController::class, 'download'])->name('admin.student.download');
    // Generate next Admission/Roll codes (AJAX)
    Route::get('student/next-codes', [StudentController::class, 'nextCodes'])->name('admin.student.next-codes');


    // Parents
    Route::get('parent/list',                  [ParentController::class, 'list'])->name('admin.parent.list');
    Route::get('parent/create',                [ParentController::class, 'add'])->name('admin.parent.add');
    Route::post('parent/add-parent',           [ParentController::class, 'insert'])->name('admin.parent.add-parent');
    Route::get('parent/edit/{id}',             [ParentController::class, 'edit'])->name('admin.parent.edit-parent');
    Route::post('parent/update/{id}',          [ParentController::class, 'update'])->name('admin.parent.update-parent');
    Route::post('parent/delete',               [ParentController::class, 'delete'])->name('admin.parent.delete-parent');
    Route::get('parent/add-my-student/{id}',   [ParentController::class, 'addMyStudent'])->name('admin.parent.add-my-student');
    Route::post('parent/assign-student',       [ParentController::class, 'assignStudent'])->name('admin.parent.assign-student');
    Route::post('parent/remove-student',       [ParentController::class, 'removeStudent'])->name('admin.parent.remove-student');
    // NEW: PDF download route (slug makes the browser tab show a nice name)
    Route::get('parents/download/{id}/{slug?}',  [ParentController::class, 'download'])->name('admin.parent.download');

    // Teachers
    Route::get('teacher/list',                 [TeacherController::class, 'list'])->name('admin.teacher.list');
    Route::get('teacher/create',               [TeacherController::class, 'add'])->name('admin.teacher.add');
    Route::post('teacher/add-teacher',         [TeacherController::class, 'insert'])->name('admin.teacher.add-teacher');
    Route::get('teacher/edit/{id}',            [TeacherController::class, 'edit'])->name('admin.teacher.edit-teacher');
    Route::post('teacher/update/{id}',         [TeacherController::class, 'update'])->name('admin.teacher.update-teacher');
    Route::post('teacher/delete',              [TeacherController::class, 'delete'])->name('admin.teacher.delete-teacher');
    // NEW: PDF download; slug makes the browser tab show the name
    Route::get('teachers/download/{id}/{slug?}', [TeacherController::class, 'download'])->name('admin.teacher.download');

    // Classes
    Route::get('class/list',                   [ClassController::class, 'classList'])->name('admin.class.list');
    Route::get('class/add',                    [ClassController::class, 'add'])->name('admin.class.add');
    Route::post('class/add-class',             [ClassController::class, 'classAdd'])->name('admin.class.add-class');
    Route::get('class/edit/{id}',              [ClassController::class, 'classEdit'])->name('admin.class.edit-class');
    Route::post('class/update/{id}',           [ClassController::class, 'classUpdate'])->name('admin.class.update-class');
    Route::post('class/delete',                [ClassController::class, 'classDelete'])->name('admin.class.delete-class');

    // Subjects
    Route::get('subject/list',                 [SubjectController::class, 'subjectList'])->name('admin.subject.list');
    Route::get('subject/add',                  [SubjectController::class, 'add'])->name('admin.subject.add');
    Route::post('subject/add-subject',         [SubjectController::class, 'subjectAdd'])->name('admin.subject.add-subject');
    Route::get('subject/edit/{id}',            [SubjectController::class, 'subjectEdit'])->name('admin.subject.edit-subject');
    Route::post('subject/update/{id}',         [SubjectController::class, 'subjectUpdate'])->name('admin.subject.update-subject');
    Route::post('subject/delete',              [SubjectController::class, 'subjectDelete'])->name('admin.subject.delete-subject');

    // Assign Subjects
    Route::get('assign_subject/list',                  [ClassSubjectController::class, 'assignSubjectList'])->name('admin.assign-subject.list');
    Route::get('assign_subject/add',                   [ClassSubjectController::class, 'add'])->name('admin.assign-subject.add');
    Route::post('assign_subject/add-subject',          [ClassSubjectController::class, 'assignSubjectAdd'])->name('admin.assign-subject.add-subject');
    Route::get('assign_subject/edit/{id}',             [ClassSubjectController::class, 'assignSubjectEdit'])->name('admin.assign-subject.edit-subject');
    Route::get('assign_subject/edit_single/{id}',      [ClassSubjectController::class, 'singleEdit'])->name('admin.assign-subject.edit-single-subject');
    Route::post('assign_subject/edit_single/{id}',     [ClassSubjectController::class, 'updateSingleEdit'])->name('admin.assign-subject.update-single-subject');
    Route::post('assign_subject/update/{id}',          [ClassSubjectController::class, 'assignSubjectUpdate'])->name('admin.assign-subject.update-subject');
    Route::post('assign_subject/delete',               [ClassSubjectController::class, 'assignSubjectDelete'])->name('admin.assign-subject.delete-subject');
    Route::get('admin/assign-subject/download',        [ClassSubjectController::class, 'download'])->name('admin.assign-subject.download');

    // Assign Class Teacher
    Route::get('assign_class_teacher/list',                    [AssignClassTeacherController::class, 'list'])->name('admin.assign-class-teacher.list');
    Route::get('assign_class_teacher/add',                     [AssignClassTeacherController::class, 'add'])->name('admin.assign-class-teacher.add');
    Route::post('assign_class_teacher/add-teacher',            [AssignClassTeacherController::class, 'assignTeacherAdd'])->name('admin.assign-class-teacher.add_teacher');
    Route::get('assign_class_teacher/edit-teacher/{id}',       [AssignClassTeacherController::class, 'assignTeacherEdit'])->name('admin.assign-class-teacher.edit_teacher');
    Route::get('assign_class_teacher/edit-single-teacher/{id}',[AssignClassTeacherController::class, 'singleTeacherEdit'])->name('admin.assign-class-teacher.edit-single-teacher');
    Route::post('assign_class_teacher/edit-single-teacher/{id}',[AssignClassTeacherController::class, 'singleTeacherUpdate'])->name('admin.assign-class-teacher.update-single-teacher');
    Route::post('assign_class_teacher/update-teacher/{id}',    [AssignClassTeacherController::class, 'assignTeacherUpdate'])->name('admin.assign-class-teacher.update_teacher');
    Route::post('assign_class_teacher/delete',                 [AssignClassTeacherController::class, 'assignTeacherDelete'])->name('admin.assign-class-teacher.delete_teacher');
    // NEW: PDF download
    Route::get('admin/assign-class-teacher/download',          [AssignClassTeacherController::class, 'download'])->name('admin.assign-class-teacher.download');

    // My account (admin)
    Route::get('account',        [UserController::class, 'myAccount'])->name('admin.account');
    Route::get('edit_account',   [UserController::class, 'editMyAccount'])->name('admin.edit-account');
    Route::post('update_account',[UserController::class, 'updateMyAccountAdmin'])->name('admin.update-account');

    // Class Timetable
    Route::get('class_timetable/list',     [ClassTimetableController::class, 'list'])->name('admin.class-timetable.list');
    Route::post('class_timetable/save',    [ClassTimetableController::class,'save'])->name('admin.class-timetable.save');
    // use {class_id} instead of {class}
    Route::get('class_timetable/subjects/{class_id}', [ClassTimetableController::class, 'subjectsForClass'])->name('admin.class-timetable.subjects');
    // NEW: PDF download
    Route::get('class_timetable/download', [ClassTimetableController::class, 'download'])->name('admin.class-timetable.download');

    // Exams
    Route::get('exam/list',                [ExamController::class, 'list'])->name('admin.exam.list');
    Route::get('exam/add',                 [ExamController::class, 'add'])->name('admin.exam.add');
    Route::post('exam/add_exam',           [ExamController::class, 'store'])->name('admin.exam.add-exam');
    Route::get('exam/edit/{exam}',         [ExamController::class, 'edit'])->name('admin.exam.edit');
    Route::post('exam/update/{exam}',      [ExamController::class, 'update'])->name('admin.exam.update');
    Route::post('exam/delete',             [ExamController::class, 'destroy'])->name('admin.exam.delete');

    // Exam Schedule
    Route::get('exam_schedule/list',       [ExamScheduleController::class, 'list'])->name('admin.exam-schedule.list');
    Route::post('exam_schedule/save',      [ExamScheduleController::class, 'save'])->name('admin.exam-schedule.save');
    Route::get('exam_schedule/subjects/{class}', [ClassTimetableController::class,'subjectsForClass'])->name('admin.exam-schedule.subjects');
    // NEW: PDF view (inline)
    Route::get('exam_schedule/download',   [ExamScheduleController::class, 'download'])->name('admin.exam-schedule.download');

    // Marks Register
    Route::get('marks_register/list',      [MarksRegisterController::class, 'list'])->name('admin.marks-register.list');
    Route::post('marks_register/save',     [MarksRegisterController::class, 'save'])->name('admin.marks-register.save');
    Route::get('marks_register/students/{class}', [MarksRegisterController::class, 'studentsForClass'])->name('admin.marks-register.students'); // JSON

    // Marks Grade
    Route::get('marks_grade/list',         [MarksGradeController::class, 'list'])->name('admin.marks-grade.list');
    Route::get('marks_grade/add',          [MarksGradeController::class, 'add'])->name('admin.marks-grade.add');
    Route::post('marks_grade/add-grade',   [MarksGradeController::class, 'addGrade'])->name('admin.marks-grade.add-grade');
    Route::get('marks_grade/edit/{id}',    [MarksGradeController::class, 'editGrade'])->name('admin.marks-grade.edit-grade');
    Route::post('marks_grade/update/{id}', [MarksGradeController::class, 'updateGrade'])->name('admin.marks-grade.update-grade');
    Route::post('marks_grade/delete',      [MarksGradeController::class, 'deleteGrade'])->name('admin.marks-grade.delete-grade');
    // NEW: download (PDF) – supports whole class or single student
    Route::get('marks-register/download', [MarksRegisterController::class, 'download'])->name('admin.marks-register.download');

    // Attendance (admin)
    Route::get('student_attendance',        [AttendanceController::class, 'studentAttendance'])->name('admin.student-attendance.view');
    Route::post('student_attendance/save',  [AttendanceController::class, 'saveStudentAttendance'])->name('admin.student-attendance.save');
    Route::get('attendance_report',         [AttendanceController::class, 'attendanceReport'])->name('admin.attendance-report.view');
    Route::get('attendance/report/download', [AttendanceController::class, 'attendanceReportDownload'])->name('admin.attendance-report.download');

    // Communicate
    Route::get('notice_board',                 [CommunicateController::class, 'noticeBoardList'])->name('admin.notice-board.list');
    Route::get('notice_board/add',             [CommunicateController::class, 'AddNoticeBoard'])->name('admin.notice-board.add');
    Route::get('notice_board/edit/{id}',       [CommunicateController::class, 'EditNoticeBoard'])->name('admin.notice-board.edit');
    Route::put('notice_board/update/{id}',     [CommunicateController::class, 'UpdateNoticeBoard'])->name('admin.notice-board.update');
    Route::post('notice_board/store',          [CommunicateController::class, 'StoreNoticeBoard'])->name('admin.notice-board.store');
    Route::delete('notice_board/{id}',         [CommunicateController::class, 'DestroyNoticeBoard'])->name('admin.notice-board.destroy');
    // NEW: download route (slug is optional, just for pretty URL)
    Route::get('notice-board/{id}/{slug?}/download', [CommunicateController::class, 'downloadNotice'])->whereNumber('id')->name('admin.notice-board.download');

    // Email
    Route::get('send-email',               [CommunicateController::class, 'emailForm'])->name('admin.email.form');
    Route::get('send-email/recipients',    [CommunicateController::class, 'searchRecipients'])->name('admin.email.recipients');
    Route::post('send-email',              [CommunicateController::class, 'emailSend'])->name('admin.email.send');
    Route::get('email-logs',               [CommunicateController::class, 'emailLogs'])->name('admin.email.logs');

    // Homework
    Route::get('homework/list',                    [HomeworkController::class, 'homeworkList'])->name('admin.homework.list');
    Route::get('homework/add',                     [HomeworkController::class, 'homeworkAdd'])->name('admin.homework.add');
    Route::post('homework/store',                  [HomeworkController::class, 'homeworkStore'])->name('admin.homework.store');
    Route::get('homework/{id}/edit',               [HomeworkController::class, 'homeworkEdit'])->name('admin.homework.edit');
    Route::put('homework/{id}/update',             [HomeworkController::class, 'homeworkUpdate'])->name('admin.homework.update');
    Route::delete('homework/{id}',                 [HomeworkController::class, 'homeworkDelete'])->name('admin.homework.delete');
    Route::post('homework/{id}/restore',           [HomeworkController::class, 'homeworkRestore'])->name('admin.homework.restore');
    Route::delete('homework/{id}/force',           [HomeworkController::class, 'homeworkForceDelete'])->name('admin.homework.force_delete');
    Route::get('homework/{id}/download',           [HomeworkController::class, 'homeworkDownload'])->name('admin.homework.download');
    Route::get('homework/subjects-by-class',       [HomeworkController::class, 'classSubjects'])->name('admin.homework.class_subjects');

    // Admin: submissions for one homework
    Route::get('homework/{homework}/submissions',                          [HomeworkController::class, 'adminHomeworkSubmissionsIndex'])->name('admin.homework.submissions.index');
    Route::get('homework/submissions/{submission}/download',               [HomeworkController::class, 'adminHomeworkSubmissionDownload'])->name('admin.homework.submissions.download');

    // Homework Report
    Route::get('homework_report',                                            [HomeworkReportController::class, 'index'])->name('admin.homework.report');
    Route::get('homework_report/homework/{homework}/download',              [HomeworkReportController::class, 'downloadHomework'])->name('admin.homework.report.download.homework');
    Route::get('homework_report/submission/{submission}/download',          [HomeworkReportController::class, 'downloadSubmission'])->name('admin.homework.report.download.submission');


    Route::prefix('fees')->name('admin.fees.')->group(function () {
        // Fee Structure
        Route::get('structures', [FeeStructureController::class, 'index'])->name('structures.index');
        Route::get('structures/create', [FeeStructureController::class, 'create'])->name('structures.create');
        Route::post('structures', [FeeStructureController::class, 'store'])->name('structures.store');
        Route::get('structures/{id}/edit', [FeeStructureController::class, 'edit'])->name('structures.edit');
        Route::put('structures/{id}', [FeeStructureController::class, 'update'])->name('structures.update');
        Route::delete('structures/{id}', [FeeStructureController::class, 'destroy'])->name('structures.destroy');

        // Invoices
        Route::get('invoices', [FeeInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/generate', [FeeInvoiceController::class, 'generateForm'])->name('invoices.generate.form');
        Route::post('invoices/generate', [FeeInvoiceController::class, 'generate'])->name('invoices.generate');
        Route::get('invoices/{id}', [FeeInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{id}/pdf', [FeeInvoiceController::class, 'pdf'])->name('invoices.pdf');

        // Payments (manual collection)
        Route::post('invoices/{id}/payments', [FeeInvoiceController::class, 'storePayment'])->name('invoices.payments.store');
        Route::delete('payments/{paymentId}', [FeeInvoiceController::class, 'deletePayment'])->name('payments.delete');

        // Reports
        Route::get('reports/class-monthly', [FeeReportController::class, 'classMonthly'])->name('reports.class-monthly');
        Route::get('class-monthly/pdf',    [FeeReportController::class, 'classMonthlyPdf'])->name('class_monthly.pdf');

        Route::get('student-statement',     [FeeReportController::class, 'studentStatement'])->name('student_statement');
        Route::get('student-statement/pdf', [FeeReportController::class, 'studentStatementPdf'])->name('student_statement.pdf');
        Route::get('reports/student-statement', [FeeReportController::class, 'studentStatement'])->name('reports.student-statement');
    });


});

/*
|--------------------------------------------------------------------------
| Teacher (school-scoped)
|--------------------------------------------------------------------------
*/
Route::prefix('teacher')->middleware(['auth', 'teacher', 'school.active'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('teacher.dashboard');

    // Account
    Route::get('account',         [UserController::class, 'myAccount'])->name('teacher.account');
    Route::get('edit_account',    [UserController::class, 'editMyAccount'])->name('teacher.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccount'])->name('teacher.update-account');

    // My classes / students
    Route::get('my_class_subject', [AssignClassTeacherController::class, 'myClassSubject'])->name('teacher.my-class-subject');
    Route::get('my_student',       [AssignClassTeacherController::class, 'myStudent'])->name('teacher.my-student');

    // Class Timetables
    Route::get('my_timetable',        [ClassTimetableController::class, 'teacherTimetable'])->name('teacher.my-timetable');
    Route::get('my_timetable/download', [ClassTimetableController::class, 'teacherDownload'])->name('teacher.my-timetable.download'); // NEW

    // Exam Timetables
    Route::get('my_exam_timetable',   [ExamScheduleController::class, 'teacherExamTimetable'])->name('teacher.my-exam-timetable');
    Route::get('my_exam_timetable/exams/{class}', [ExamScheduleController::class, 'examsForClass'])->name('teacher.my-exam-timetable.exams');
    Route::get('my_exam_timetable/download',              [ExamScheduleController::class, 'teacherExamTimetableDownload'])->name('teacher.my-exam-timetable.download'); // NEW

    // Marks Register
    Route::get('marks_register/list',  [MarksRegisterController::class, 'teacherMarkRegisterList'])->name('teacher.marks-register.list');
    Route::post('marks_register/save', [MarksRegisterController::class, 'teacherMarkRegisterSave'])->name('teacher.marks-register.save');
    Route::get('marks_register/students/{class}', [MarksRegisterController::class, 'teacherStudentsForClass'])->name('teacher.marks-register.students');
    Route::get('marks_register/download', [MarksRegisterController::class, 'teacherDownload'])->name('teacher.marks-register.download');

    // Attendance
    Route::get('student_attendance',        [AttendanceController::class, 'teacherAttendance'])->name('teacher.student-attendance.view');
    Route::post('student_attendance/save',  [AttendanceController::class, 'teacherAttendanceSave'])->name('teacher.student-attendance.save');
    Route::get('attendance_report',         [AttendanceController::class, 'teacherAttendanceReport'])->name('teacher.attendance-report.view');
    Route::get('attendance/report/download', [AttendanceController::class, 'teacherAttendanceReportDownload'])->name('teacher.attendance-report.download'); // NEW

    // Notices & Inbox
    Route::get('my_notice_board', [CommunicateController::class,'teacherNotices'])->name('teacher.notice-board');
    Route::get('inbox',           [CommunicateController::class,'teacherInbox'])->name('teacher.inbox');
    Route::get('inbox/{log}',     [CommunicateController::class,'showInboxItem'])->name('teacher.inbox.show');

    // Homework (teacher)
    Route::get('homework/list',                 [HomeworkController::class, 'teacherHomeworkList'])->name('teacher.homework.list');
    Route::get('homework/add',                  [HomeworkController::class, 'teacherHomeworkAdd'])->name('teacher.homework.add');
    Route::post('homework/store',               [HomeworkController::class, 'teacherHomeworkStore'])->name('teacher.homework.store');
    Route::get('homework/{id}/edit',            [HomeworkController::class, 'teacherHomeworkEdit'])->name('teacher.homework.edit');
    Route::put('homework/{id}/update',          [HomeworkController::class, 'teacherHomeworkUpdate'])->name('teacher.homework.update');
    Route::delete('homework/{id}',              [HomeworkController::class, 'teacherHomeworkDelete'])->name('teacher.homework.delete');
    Route::get('homework/{id}/download',        [HomeworkController::class, 'teacherHomeworkDownload'])->name('teacher.homework.download');
    Route::get('homework/subjects-by-class',    [HomeworkController::class, 'teacherHomeworkClassSubjects'])->name('teacher.homework.class_subjects');
    Route::get('homework/{homework}/submissions',[HomeworkController::class, 'teacherHomeworkSubmissionsIndex'])->name('teacher.homework.submissions.index');
    Route::get('homework/submissions/{submission}/download', [HomeworkController::class, 'teacherHomeworkSubmissionDownload'])->name('teacher.homework.submissions.download');
});

/*
|--------------------------------------------------------------------------
| Student (school-scoped)
|--------------------------------------------------------------------------
*/
Route::prefix('student')->middleware(['auth', 'student', 'school.active'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('student.dashboard');
    Route::get('my-fees', [MyFeesController::class, 'index'])->name('student.fees.index');

    // Account
    Route::get('account',         [UserController::class, 'myAccount'])->name('student.account');
    Route::get('edit_account',    [UserController::class, 'editMyAccount'])->name('student.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccountStudent'])->name('student.update-account');

    // Timetables & calendars
    Route::get('my_timetable',       [ClassTimetableController::class, 'myTimetablelist'])->name('student.my-timetable');
    Route::get('my_exam_timetable',  [ExamScheduleController::class, 'studentExamTimetable'])->name('student.my-exam-timetable');
    Route::get('my_calendar',        [CalendarController::class, 'myCalendar'])->name('student.my-calendar');
    Route::get('calendar/download', [CalendarController::class,'downloadClassRoutine'])->name('student.calendar.download');
    Route::get('my_exam_calendar',   [CalendarController::class, 'myExamCalendar'])->name('student.my-exam-calendar');
    Route::get('exam-calendar/download', [CalendarController::class,'downloadExamSchedule'])->name('student.exam-calendar.download');


    // Marks
    Route::get('marks_register/list',[MarksRegisterController::class, 'studentMarkRegisterList'])->name('student.marks-register.list');
    Route::get('student/marks-register/download', [MarksRegisterController::class, 'studentResultDownload'])->name('student.marks-register.download');

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'studentMonthlyAttendance'])->name('student.attendance.month');

    // Notices & Inbox
    Route::get('my_notice_board', [CommunicateController::class,'studentNotices'])->name('student.notice-board');
    Route::get('inbox',           [CommunicateController::class,'studentInbox'])->name('student.inbox');
    Route::get('inbox/{log}',     [CommunicateController::class,'showInboxItem'])->name('student.inbox.show');

    // Homework (student)
    Route::get('homework/list',                          [HomeworkController::class, 'studentHomeworkList'])->name('student.homework.list');
    Route::get('homework/{homework}/submit',             [HomeworkController::class, 'studentSubmitHomework'])->name('student.homework.submit');
    Route::post('homework/{homework}/submit',            [HomeworkController::class, 'studentSubmitHomeworkStore'])->name('student.homework.submit.store');
    Route::get('homework/{homework}/download',           [HomeworkController::class, 'studentHomeworkDownload'])->name('student.homework.download');
    Route::get('homework/submitted',                     [HomeworkController::class, 'studentSubmitHomeworkList'])->name('student.homework.submitted');
    Route::get('homework/submission/{id}/download',      [HomeworkController::class, 'studentSubmitHomeworkDownload'])->name('student.homework.submission.download');
});

/*
|--------------------------------------------------------------------------
| Parent (school-scoped)
|--------------------------------------------------------------------------
*/
Route::prefix('parent')->middleware(['auth', 'parent', 'school.active'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('parent.dashboard');
    Route::get('child-fees', [ChildFeesController::class, 'index'])->name('parent.fees.index');

    // Account
    Route::get('account',         [UserController::class, 'myAccount'])->name('parent.account');
    Route::get('edit_account',    [UserController::class, 'editMyAccount'])->name('parent.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccountParent'])->name('parent.update-account');

    // Timetables
    Route::get('my_timetable',        [ClassTimetableController::class, 'parentTimetable'])->name('parent.my-timetable');
    Route::get('my_exam_timetable',   [ExamScheduleController::class, 'parentExamTimetable'])->name('parent.my-exam-timetable');
    Route::get('my_exam_timetable/exams/{student}', [ExamScheduleController::class, 'examsForStudent'])->name('parent.my-exam-timetable.exams');

    // Marks
    Route::get('marks_register/list', [MarksRegisterController::class, 'parentMarkRegisterList'])->name('parent.marks-register.list');

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'parentMonthlyAttendance'])->name('parent.attendance.month');

    // Notices & Inbox
    Route::get('my_notice_board', [CommunicateController::class,'parentNotices'])->name('parent.notice-board');
    Route::get('inbox',           [CommunicateController::class,'parentInbox'])->name('parent.inbox');
    Route::get('inbox/{log}',     [CommunicateController::class,'showInboxItem'])->name('parent.inbox.show');

    // Child Homework
    Route::get('child/homework',                                       [HomeworkController::class, 'parentChildHomeworkList'])->name('parent.child.homework.list');
    Route::get('child/homework/{homework}/download',                   [HomeworkController::class, 'parentChildHomeworkDownload'])->name('parent.child.homework.download');
    Route::get('child/homework/{homework}/submission/{student}',       [HomeworkController::class, 'parentChildSubmissionShow'])->name('parent.child.homework.submission.show');
    Route::get('child/submissions/{submission}/download',              [HomeworkController::class, 'parentChildSubmissionDownload'])->name('parent.child.submissions.download');
});
