<?php

use Illuminate\Support\Facades\Route;
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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Welcome Route
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin Public Routes (Login, Logout, Forgot Password)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('login', [AdminController::class, 'loginPage'])->name('admin.login.page');
    Route::post('login', [AdminController::class, 'login'])->name('admin.login');
    Route::get('logout', [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('forgot-password', [AdminController::class, 'forgotPassword'])->name('admin.forgotPassword');
});

/*
|--------------------------------------------------------------------------
| Admin Protected Routes (Requires 'auth' and 'admin' middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('list', [AdminController::class, 'list'])->name('admin.admin.list');
    Route::get('add', [AdminController::class, 'add'])->name('admin.admin.add');
    Route::post('add-admin', [AdminController::class, 'addAdmin'])->name('admin.admin.add-admin');
    Route::get('edit/{id}', [AdminController::class, 'editAdmin'])->name('admin.admin.edit-admin');
    Route::post('update/{id}', [AdminController::class, 'updateAdmin'])->name('admin.admin.update-admin');
    Route::post('delete', [AdminController::class, 'deleteAdmin'])->name('admin.admin.delete-admin');

    // Student Urls
    Route::get('student/list', [StudentController::class, 'list'])->name('admin.student.list');
    Route::get('student/create', [StudentController::class, 'add'])->name('admin.student.add');
    Route::post('student/add-student', [StudentController::class, 'insert'])->name('admin.student.add-student');
    Route::get('student/edit/{id}', [StudentController::class, 'edit'])->name('admin.student.edit-student');
    Route::post('student/update/{id}', [StudentController::class, 'update'])->name('admin.admin.update-student');
    Route::post('student/delete', [StudentController::class, 'delete'])->name('admin.student.delete-student');

    // Parent Urls
    Route::get('parent/list', [ParentController::class, 'list'])->name('admin.parent.list');
    Route::get('parent/create', [ParentController::class, 'add'])->name('admin.parent.add');
    Route::post('parent/add-parent', [ParentController::class, 'insert'])->name('admin.parent.add-parent');
    Route::get('parent/edit/{id}', [ParentController::class, 'edit'])->name('admin.parent.edit-parent');
    Route::post('parent/update/{id}', [ParentController::class, 'update'])->name('admin.parent.update-parent');
    Route::post('parent/delete', [ParentController::class, 'delete'])->name('admin.parent.delete-parent');
    Route::get('parent/add-my-student/{id}', [ParentController::class, 'addMyStudent'])->name('admin.parent.add-my-student');
    Route::post('parent/assign-student', [ParentController::class, 'assignStudent'])->name('admin.parent.assign-student');
    Route::post('parent/remove-student', [ParentController::class, 'removeStudent'])->name('admin.parent.remove-student');

    // Teacher Urls
    Route::get('teacher/list', [TeacherController::class, 'list'])->name('admin.teacher.list');
    Route::get('teacher/create', [TeacherController::class, 'add'])->name('admin.teacher.add');
    Route::post('teacher/add-teacher', [TeacherController::class, 'insert'])->name('admin.teacher.add-teacher');
    Route::get('teacher/edit/{id}', [TeacherController::class, 'edit'])->name('admin.teacher.edit-teacher');
    Route::post('teacher/update/{id}', [TeacherController::class, 'update'])->name('admin.teacher.update-teacher');
    Route::post('teacher/delete', [TeacherController::class, 'delete'])->name('admin.teacher.delete-teacher');


    // Class Urls

    Route::get('class/list', [ClassController::class, 'classList'])->name('admin.class.list');
    Route::get('class/add', [ClassController::class, 'add'])->name('admin.class.add');
    Route::post('class/add-class', [ClassController::class, 'classAdd'])->name('admin.class.add-class');
    Route::get('class/edit/{id}', [ClassController::class, 'classEdit'])->name('admin.class.edit-class');
    Route::post('class/update/{id}', [ClassController::class, 'classUpdate'])->name('admin.class.update-class');
    Route::post('class/delete', [ClassController::class, 'classDelete'])->name('admin.class.delete-class');

    // Subject Urls
    Route::get('subject/list', [SubjectController::class, 'subjectList'])->name('admin.subject.list');
    Route::get('subject/add', [SubjectController::class, 'add'])->name('admin.subject.add');
    Route::post('subject/add-subject', [SubjectController::class, 'subjectAdd'])->name('admin.subject.add-subject');
    Route::get('subject/edit/{id}', [SubjectController::class, 'subjectEdit'])->name('admin.subject.edit-subject');
    Route::post('subject/update/{id}', [SubjectController::class, 'subjectUpdate'])->name('admin.subject.update-subject');
    Route::post('subject/delete', [SubjectController::class, 'subjectDelete'])->name('admin.subject.delete-subject');

    // Assign-Subject Urls
    Route::get('assign_subject/list', [ClassSubjectController::class, 'assignSubjectList'])->name('admin.assign-subject.list');
    Route::get('assign_subject/add', [ClassSubjectController::class, 'add'])->name('admin.assign-subject.add');
    Route::post('assign_subject/add-subject', [ClassSubjectController::class, 'assignSubjectAdd'])->name('admin.assign-subject.add-subject');
    Route::get('assign_subject/edit/{id}', [ClassSubjectController::class, 'assignSubjectEdit'])->name('admin.assign-subject.edit-subject');
    Route::get('assign_subject/edit_single/{id}', [ClassSubjectController::class, 'singleEdit'])->name('admin.assign-subject.edit-single-subject');
    Route::post('assign_subject/edit_single/{id}', [ClassSubjectController::class, 'updateSingleEdit'])->name('admin.assign-subject.update-single-subject');
    Route::post('assign_subject/update/{id}', [ClassSubjectController::class, 'assignSubjectUpdate'])->name('admin.assign-subject.update-subject');
    Route::post('assign_subject/delete', [ClassSubjectController::class, 'assignSubjectDelete'])->name('admin.assign-subject.delete-subject');

    // Assign Class Teacher Urls
    Route::get('assign_class_teacher/list', [AssignClassTeacherController::class, 'list'])->name('admin.assign-class-teacher.list');
    Route::get('assign_class_teacher/add', [AssignClassTeacherController::class, 'add'])->name('admin.assign-class-teacher.add');
    Route::post('assign_class_teacher/add-teacher', [AssignClassTeacherController::class, 'assignTeacherAdd'])->name('admin.assign-class-teacher.add_teacher');
    Route::get('assign_class_teacher/edit-teacher/{id}', [AssignClassTeacherController::class, 'assignTeacherEdit'])->name('admin.assign-class-teacher.edit_teacher');

    // ✅ use a unique URI here
    Route::get('assign_class_teacher/edit-single-teacher/{id}',  [AssignClassTeacherController::class, 'singleTeacherEdit'])->name('admin.assign-class-teacher.edit-single-teacher');
    Route::post('assign_class_teacher/edit-single-teacher/{id}', [AssignClassTeacherController::class, 'singleTeacherUpdate'])->name('admin.assign-class-teacher.update-single-teacher');

    Route::post('assign_class_teacher/update-teacher/{id}', [AssignClassTeacherController::class, 'assignTeacherUpdate'])->name('admin.assign-class-teacher.update_teacher');
    Route::post('assign_class_teacher/delete', [AssignClassTeacherController::class, 'assignTeacherDelete'])->name('admin.assign-class-teacher.delete_teacher');

    // My account
    Route::get('account', [UserController::class, 'myAccount'])->name('admin.account');
    Route::get('edit_account', [UserController::class, 'editMyAccount'])->name('admin.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccountAdmin'])->name('admin.update-account');


    // Class Timetable
    Route::get('class_timetable/list', [ClassTimetableController::class, 'list'])->name('admin.class-timetable.list');
    Route::post('class_timetable/save',  [ClassTimetableController::class,'save'])->name('admin.class-timetable.save');

    // AJAX: subjects assigned to a class
    Route::get('class_timetable/subjects/{class}', [ClassTimetableController::class,'subjectsForClass'])
        ->name('admin.class-timetable.subjects');

    // Examination (Exam List)
    Route::get('exam/list', [ExamController::class, 'list'])->name('admin.exam.list');
    Route::get('exam/add', [ExamController::class, 'add'])->name('admin.exam.add');
    Route::post('exam/add_exam', [ExamController::class, 'store'])->name('admin.exam.add-exam');

    Route::get('exam/edit/{exam}',    [ExamController::class, 'edit'])->name('admin.exam.edit'); // show same form with data
    Route::post('exam/update/{exam}', [ExamController::class, 'update'])->name('admin.exam.update');

    Route::post('exam/delete', [ExamController::class, 'destroy'])->name('admin.exam.delete');   // soft delete


    // Examination (Exam Schedule)
    Route::get('exam_schedule/list', [ExamScheduleController::class, 'list'])->name('admin.exam-schedule.list');

    Route::post('exam_schedule/save', [ExamScheduleController::class, 'save'])->name('admin.exam-schedule.save');

    //(Optional) reuse your existing AJAX route for subjects if you want dynamic loads
    Route::get('exam_schedule/subjects/{class}', [ClassTimetableController::class,'subjectsForClass'])->name('admin.exam-schedule.subjects');

    // Marks Register
    Route::get('marks_register/list', [MarksRegisterController::class, 'list'])->name('admin.marks-register.list');
    Route::post('marks_register/save', [MarksRegisterController::class, 'save'])->name('admin.marks-register.save');

    // Marks Grade
    Route::get('marks_grade/list', [MarksGradeController::class, 'list'])->name('admin.marks-grade.list');
    Route::get('marks_grade/add', [MarksGradeController::class, 'add'])->name('admin.marks-grade.add');
    Route::post('marks_grade/add-grade', [MarksGradeController::class, 'addGrade'])->name('admin.marks-grade.add-grade');
    Route::get('marks_grade/edit/{id}', [MarksGradeController::class, 'editGrade'])->name('admin.marks-grade.edit-grade');
    Route::post('marks_grade/update/{id}', [MarksGradeController::class, 'updateGrade'])->name('admin.marks-grade.update-grade');
    Route::post('marks_grade/delete', [MarksGradeController::class, 'deleteGrade'])->name('admin.marks-grade.delete-grade');

    // Attendance
    Route::get('student_attendance', [AttendanceController::class, 'studentAttendance'])->name('admin.student-attendance.view');
    Route::post('student_attendance/save',  [AttendanceController::class, 'saveStudentAttendance'])->name('admin.student-attendance.save');

    // Attendance Report (Admin)
    Route::get('attendance_report', [AttendanceController::class, 'attendanceReport'])->name('admin.attendance-report.view');

    // Communicate (Notice Board, Send Email)
    Route::get('notice_board', [CommunicateController::class, 'noticeBoardList'])->name('admin.notice-board.list');
    Route::get('notice_board/add', [CommunicateController::class, 'AddNoticeBoard'])->name('admin.notice-board.add');
    Route::get('notice_board/edit/{id}', [CommunicateController::class, 'EditNoticeBoard'])->name('admin.notice-board.edit');
    Route::put('notice_board/update/{id}', [CommunicateController::class, 'UpdateNoticeBoard'])->name('admin.notice-board.update');
    Route::post('notice_board/store', [CommunicateController::class, 'StoreNoticeBoard'])->name('admin.notice-board.store');
    Route::delete('notice_board/{id}', [CommunicateController::class, 'DestroyNoticeBoard'])->name('admin.notice-board.destroy');

    // Send Email (form, search recipients, send)
    Route::get('send-email', [CommunicateController::class, 'emailForm'])->name('admin.email.form');
    Route::get('send-email/recipients', [CommunicateController::class, 'searchRecipients'])->name('admin.email.recipients'); // <= inside group
    Route::post('send-email', [CommunicateController::class, 'emailSend'])->name('admin.email.send');
    Route::get('email-logs', [CommunicateController::class, 'emailLogs'])->name('admin.email.logs');

    // Homework
    Route::get('homework/list', [HomeworkController::class, 'homeworkList'])->name('admin.homework.list');

    Route::get('homework/add', [HomeworkController::class, 'homeworkAdd'])->name('admin.homework.add');
    Route::post('homework/store', [HomeworkController::class, 'homeworkStore'])->name('admin.homework.store');

    Route::get('homework/{id}/edit', [HomeworkController::class, 'homeworkEdit'])->name('admin.homework.edit');
    Route::put('homework/{id}/update', [HomeworkController::class, 'homeworkUpdate'])->name('admin.homework.update');

    Route::delete('homework/{id}', [HomeworkController::class, 'homeworkDelete'])->name('admin.homework.delete');
    Route::post('homework/{id}/restore', [HomeworkController::class, 'homeworkRestore'])->name('admin.homework.restore');
    Route::delete('homework/{id}/force', [HomeworkController::class, 'homeworkForceDelete'])->name('admin.homework.force_delete');

    Route::get('homework/{id}/download', [HomeworkController::class, 'homeworkDownload'])->name('admin.homework.download');

    // AJAX for subjects by class
    Route::get('homework/subjects-by-class', [HomeworkController::class, 'classSubjects'])->name('admin.homework.class_subjects');

    // Submissions list for a homework
    Route::get('homework/{homework}/submissions', [HomeworkController::class, 'adminHomeworkSubmissionsIndex'])
        ->name('admin.homework.submissions.index');

    // Download a student's submission attachment
    Route::get('homework/submissions/{submission}/download', [HomeworkController::class, 'adminHomeworkSubmissionDownload'])
        ->name('admin.homework.submissions.download');



});

/*
|--------------------------------------------------------------------------
| Teacher Protected Routes (Requires 'auth' and 'teacher' middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('teacher')->middleware(['auth', 'teacher'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('teacher.dashboard');
    Route::get('account', [UserController::class, 'myAccount'])->name('teacher.account');
    Route::get('edit_account', [UserController::class, 'editMyAccount'])->name('teacher.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccount'])->name('teacher.update-account');
    // My Class Subject
    Route::get('my_class_subject', [AssignClassTeacherController::class, 'myClassSubject'])->name('teacher.my-class-subject');
    // My Student
    Route::get('my_student', [AssignClassTeacherController::class, 'myStudent'])->name('teacher.my-student');

    // Class Timetable
    Route::get('my_timetable', [ClassTimetableController::class, 'teacherTimetable'])->name('teacher.my-timetable');

    //Exam Timetable
    Route::get('my_exam_timetable', [ExamScheduleController::class, 'teacherExamTimetable'])
        ->name('teacher.my-exam-timetable');

    // AJAX: exams available for a given class (only if there are schedules with active subjects)
    Route::get('my_exam_timetable/exams/{class}', [ExamScheduleController::class, 'examsForClass'])
        ->name('teacher.my-exam-timetable.exams');

    // Marks Register
    Route::get('marks_register/list', [MarksRegisterController::class, 'teacherMarkRegisterList'])->name('teacher.marks-register.list');
    Route::post('marks_register/save', [MarksRegisterController::class, 'teacherMarkRegisterSave'])->name('teacher.marks-register.save');

    // Attendance
    Route::get('student_attendance',       [AttendanceController::class, 'teacherAttendance'])->name('teacher.student-attendance.view');
    Route::post('student_attendance/save', [AttendanceController::class, 'teacherAttendanceSave'])->name('teacher.student-attendance.save');

    // Teacher → Attendance Report (view only)
    Route::get('teacher/attendance_report', [AttendanceController::class, 'teacherAttendanceReport'])->name('teacher.attendance-report.view');

    // Notice Board
    Route::get('my_notice_board', [CommunicateController::class,'teacherNotices'])->name('teacher.notice-board');

    // My Email
    Route::get('inbox', [CommunicateController::class,'teacherInbox'])->name('teacher.inbox');
    Route::get('inbox/{log}', [CommunicateController::class,'showInboxItem'])->name('teacher.inbox.show');

    // Homework
    Route::get('homework/list', [HomeworkController::class, 'teacherHomeworkList'])->name('teacher.homework.list');

    Route::get('homework/add', [HomeworkController::class, 'teacherHomeworkAdd'])->name('teacher.homework.add');
    Route::post('homework/store', [HomeworkController::class, 'teacherHomeworkStore'])->name('teacher.homework.store');

    Route::get('homework/{id}/edit', [HomeworkController::class, 'teacherHomeworkEdit'])->name('teacher.homework.edit');
    Route::put('homework/{id}/update', [HomeworkController::class, 'teacherHomeworkUpdate'])->name('teacher.homework.update');

    Route::delete('homework/{id}', [HomeworkController::class, 'teacherHomeworkDelete'])->name('teacher.homework.delete');

    Route::get('homework/{id}/download', [HomeworkController::class, 'teacherHomeworkDownload'])->name('teacher.homework.download');

    // AJAX: subjects by class (restricted to teacher's classes)
    Route::get('homework/subjects-by-class',[HomeworkController::class, 'teacherHomeworkClassSubjects'])->name('teacher.homework.class_subjects');

    // Submissions list (for one homework)
    Route::get('homework/{homework}/submissions', [HomeworkController::class, 'teacherHomeworkSubmissionsIndex'])->name('teacher.homework.submissions.index');

// Download a student's submission attachment
    Route::get('homework/submissions/{submission}/download', [HomeworkController::class, 'teacherHomeworkSubmissionDownload'])->name('teacher.homework.submissions.download');

});

/*
|--------------------------------------------------------------------------
| Student Protected Routes (Requires 'auth' and 'student' middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('student')->middleware(['auth', 'student'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('student.dashboard');
    Route::get('account', [UserController::class, 'myAccount'])->name('student.account');
    Route::get('edit_account', [UserController::class, 'editMyAccount'])->name('student.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccountStudent'])->name('student.update-account');

    // Class Timetable
    Route::get('my_timetable', [ClassTimetableController::class, 'myTimetablelist'])->name('student.my-timetable');

    //Exam Timetable
    Route::get('my_exam_timetable', [ExamScheduleController::class, 'studentExamTimetable'])->name('student.my-exam-timetable');

    // My Calendar
    Route::get('my_calendar', [CalendarController::class, 'myCalendar'])->name('student.my-calendar');
    // My Exam Calendar
    Route::get('my_exam_calendar', [CalendarController::class, 'myExamCalendar'])->name('student.my-exam-calendar');

    // Marks Register
    Route::get('marks_register/list', [MarksRegisterController::class, 'studentMarkRegisterList'])->name('student.marks-register.list');

    //Attendance
    Route::get('attendance', [AttendanceController::class, 'studentMonthlyAttendance'])->name('student.attendance.month');

    // Notice Board
    Route::get('my_notice_board', [CommunicateController::class,'studentNotices'])->name('student.notice-board');

    // My Email
    Route::get('inbox', [CommunicateController::class,'studentInbox'])->name('student.inbox');
    Route::get('inbox/{log}', [CommunicateController::class,'showInboxItem'])->name('student.inbox.show');

    // Sidebar #1: My Homework
    Route::get('homework/list', [HomeworkController::class, 'studentHomeworkList'])->name('student.homework.list');
    Route::get('homework/{homework}/submit', [HomeworkController::class, 'studentSubmitHomework'])->name('student.homework.submit');
    Route::post('homework/{homework}/submit',[HomeworkController::class, 'studentSubmitHomeworkStore'])->name('student.homework.submit.store');
    Route::get('homework/{homework}/download',[HomeworkController::class, 'studentHomeworkDownload'])->name('student.homework.download');

    // Sidebar #2: Submitted Homework
    Route::get('homework/submitted', [HomeworkController::class, 'studentSubmitHomeworkList'])->name('student.homework.submitted');
    Route::get('homework/submission/{id}/download', [HomeworkController::class, 'studentSubmitHomeworkDownload'])->name('student.homework.submission.download');


});

/*
|--------------------------------------------------------------------------
| Parent Protected Routes (Requires 'auth' and 'parent' middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('parent')->middleware(['auth', 'parent'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('parent.dashboard');
    Route::get('account', [UserController::class, 'myAccount'])->name('parent.account');
    Route::get('edit_account', [UserController::class, 'editMyAccount'])->name('parent.edit-account');
    Route::post('update_account', [UserController::class, 'updateMyAccountParent'])->name('parent.update-account');

    // Class Timetable
    Route::get('my_timetable', [ClassTimetableController::class, 'parentTimetable'])->name('parent.my-timetable');

    //Exam Timetable
    Route::get('my_exam_timetable', [ExamScheduleController::class, 'parentExamTimetable'])
        ->name('parent.my-exam-timetable');

    // AJAX: exams available for a given student (based on the student's class, only active subjects)
    Route::get('my_exam_timetable/exams/{student}', [ExamScheduleController::class, 'examsForStudent'])
        ->name('parent.my-exam-timetable.exams');

    // Marks Register
    Route::get('marks_register/list', [MarksRegisterController::class, 'parentMarkRegisterList'])->name('parent.marks-register.list');

    //Attendance
    Route::get('attendance', [AttendanceController::class, 'parentMonthlyAttendance'])->name('parent.attendance.month');

    // Notice Board
    Route::get('my_notice_board', [CommunicateController::class,'parentNotices'])->name('parent.notice-board');

    // My Email
    Route::get('inbox', [CommunicateController::class,'parentInbox'])->name('parent.inbox');
    Route::get('inbox/{log}', [CommunicateController::class,'showInboxItem'])->name('parent.inbox.show');

    // My Childs Homework
    Route::get('child/homework',                [HomeworkController::class, 'parentChildHomeworkList'])->name('parent.child.homework.list');
    Route::get('child/homework/{homework}/download', [HomeworkController::class, 'parentChildHomeworkDownload'])->name('parent.child.homework.download');

    // NEW: view a child’s submission for a specific homework
    Route::get('child/homework/{homework}/submission/{student}', [HomeworkController::class, 'parentChildSubmissionShow'])->name('parent.child.homework.submission.show');

    // NEW: download the child’s submission attachment
    Route::get('child/submissions/{submission}/download', [HomeworkController::class, 'parentChildSubmissionDownload'])->name('parent.child.submissions.download');

});
