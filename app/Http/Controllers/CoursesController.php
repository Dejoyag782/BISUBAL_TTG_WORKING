<?php

namespace App\Http\Controllers;

use Response;
use Illuminate\Http\Request;
use App\Services\CoursesService;

use App\Models\Course;
use App\Models\Professor;
use DB;
class CoursesController extends Controller
{
    /**
     * Service class for handling operations relating to this
     * controller
     *
     * @var App\Services\CoursesService $service
     */
    protected $service;

    public function __construct(CoursesService $service)
    {
        $this->middleware('auth');
        $this->middleware('activated');
        $this->service = $service;
    }

    /**
     * Get a listing of courses
     *
     * @param Illuminate\Http\Request $request The HTTP request
     */
    public function index(Request $request)
    {
        $courses = $this->service->all([
            'keyword' => $request->has('keyword') ? $request->keyword : null,
            'filter' => $request->has('filter') ? $request->filter : null,
            'order_by' => 'course_code',
            'paginate' => 'true',
            'per_page' => 20
        ]);

        $professors = Professor::all();

        $rooms = DB::table('rooms')->select('id','name')->get();

        // Fetch the room names for the courses
        foreach ($courses as $course) {
            $room = $rooms->where('id', $course->room_preference)->first(); // Find the room with the corresponding ID
            $course->room_name = $room ? $room->name : ''; // Set the room name as a property of the course
        }

        if ($request->ajax()) {
            return view('courses.table', compact('courses'));
        }

        return view('courses.index', compact('courses', 'professors', 'rooms'));
    }

    /**
     * Add a new course
     *
     * @param Illuminate\Http\Request $request The HTTP request
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'course_code' => 'required|unique:courses,course_code',
        ];

        $messages = [
            'name.unique' => 'This course already exists',
        ];

        $this->validate($request, $rules, $messages);

        $course = $this->service->store($request->all());

        if ($course) {
            return response()->json(['message' => 'Course added'], 200);
        } else {
            return response()->json(['error' => 'A system error occurred'], 500);
        }
    }

    /**
     * Get a room by id
     *
     * @param int id The id of the room
     * @param Illuminate\Http\Request $request HTTP request
     */
    public function show($id, Request $request)
    {
        $course = $this->service->show($id);

        if ($course) {
            return response()->json($course, 200);
        } else {
            return response()->json(['error' => 'Course not found'], 404);
        }
    }

    /**
     * Update room with given ID
     *
     * @param int id The id of the room to be updated
     * @param Illuminate\Http\Request The HTTP request
     */
    public function update($id, Request $request)
    {
        $rules = [
            'name' => 'required',
            'course_code' => 'required|unique:courses,course_code,' . $id,
        ];

        $messages = [
            'name.unique' => 'This course already exists'
        ];

        $this->validate($request, $rules, $messages);

        $course = $this->service->show($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $course = $this->service->update($id, $request->all());

        return response()->json(['message' => 'Course updated'], 200);
    }

    /**
     * Delete the course whose id is given
     *
     * @param int $id The id of course to be deleted
     */
    public function destroy($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        if ($this->service->delete($id)) {
            return response()->json(['message' => 'Course has been deleted'], 200);
        } else {
            return response()->json(['error' => 'An unknown system error occurred'], 500);
        }
    }
}
