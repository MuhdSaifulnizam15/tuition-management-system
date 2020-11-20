<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Student;
use App\Parents;
use Illuminate\Http\Request;
use App\Http\Resources\StudentResource;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:student-list'], ['only' => ['index']]);
        $this->middleware(['permission:student-create'], ['only' => ['store']]);
        $this->middleware(['permission:student-edit'], ['only' => ['update']]);
        $this->middleware(['permission:student-delete'], ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->has('limit') ? $request->limit : 10;
        
        $students = Student::with('parents')
            ->applyFilters($request->only([
                'search',
                'full_name',
                'nric',
                'mobile_no',
                'orderByField',
                'orderBy'
            ]))
            ->whereBranch($request->header('branch'))
            ->groupBy('students.id')
            ->paginate($limit);

        return response()->json([
            'students' => $students
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $student = $request->all();

        $validator = Validator::make($student, [
            'full_name' => 'required',
            'nric' => 'required',
            'age' => 'required',
            'level' => 'required',
        ]);

        if($validator->fails()){
            return response([
                'status' => false,
                'error' => $validator->errors(),
                'message' => 'Validation Error'
            ], 401);
        }

        $student = Student::create($student);

        return response([
            'status' => true,
            'data' => new StudentResource($student),
            'message' => 'Student successfully created',
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show(Student $student)
    {
        $students = Student::with('parents')
            ->where('students.id', '=', $student->id)
            ->get();
        
        return response([
            'status' => true,
            'data' => $students
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Student $student)
    {
        if(auth()->user()){
            $student->update($request->all());

            return response([
                'status' => true,
                'message' => 'Student successfully updated',
                'data' => new StudentResource($student),
            ], 200);
        } else {
            return response([
                'status' => false,
                'message' => 'Failed to update the student'
            ], 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy(Student $student)
    {
        if(auth()->user()){
            $student->delete();

            return response([
                'status' => true,
                'message' => 'Student successfully deleted'
            ], 200);
        } else {
            return response([
                'status' => false,
                'message' => 'Failed to delete the student'
            ], 401);
        }
    }
}
