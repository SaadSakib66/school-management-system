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

});
