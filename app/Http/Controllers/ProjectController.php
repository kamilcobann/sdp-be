<?php

namespace App\Http\Controllers;

use App\Models\Kanban;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function getAllProjects(Request $request)
    {

        try {
            $projects = Project::withCount('kanbans')->with(['members','kanbans.kanbanLists.tickets.comments'])->get();
            return response()->json([
                "status" => true,
                "message" => "All projects are successfully retrieved.",
                "projects" => $projects
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function getAllProjectsOfUser(Request $request)
    {

        try {
            $projects = Project::where('by_user_id', $request->user()->id)->with(['members','kanbans.kanbanLists.tickets.comments'])->get();

            return response()->json([
                "status" => true,
                "message" => "All projects are successfully retrieved.",
                "projects" => $projects
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }


    public function getProjectById($id)
    {

        try {
            $project = Project::with(['members','kanbans.kanbanLists.tickets.comments'])->withCount('members')->findOrFail($id);

            return response()->json([
                "status" => true,
                "message" => "Project successfully retrieved.",
                "project" => $project
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 404);
        }
    }

    public function deleteProjectById($id)
    {
        try {
            if (!$project = Project::find($id)) {
                return response()->json([
                    "status" => false,
                    "message" => "Project with id: " . $id . " not found."
                ], 404);
            }

            $project->delete();
            return response()->json([
                "status" => true,
                "message" => "Project deleted."
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function createProject(Request $request)
    {

        try {
            $project = $request->user()->ownedProjects()->create(
                $request->validate([
                    "title" => "string|required|min:0|max:100",
                    "description" => "string|required|min:0|max:255",
                    "start_date" => "date|required|after:today",
                    "end_date" => "date|required|after:start_date",
                    "is_active" => "boolean",
                    "status" => "string"
                ])
            );

            return response()->json([
                "status" => true,
                "message" => "Project created.",
                "project" => $project
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }


    public function updateProject(Request $request, $id)
    {
        try {
            $project = Project::whereId($id)->where("by_user_id", $request->user()->id)->with(['members','kanbans.kanbanLists'])->first();
            $validator = Validator::make($request->all(), [
                "title" => "string|min:0|max:100",
                "description" => "string|min:0|max:255",
                "start_date" => "date|after:today",
                "end_date" => "date|after:start_date",
                "is_active" => "boolean"
            ]);

            if($validator->fails()){
                $errors = $validator->errors();
                return response()->json([
                    "status"=>false,
                    "message"=>$errors,
                ],400);
            }

            $project->title = $request->title;
            $project->description = $request->description;
            $project->start_date = $request->start_date;
            $project->end_date = $request->end_date;
            $project->is_active = $request->is_active;

            $project->save();

            return response()->json([
                "status" => true,
                "message" => "Project updated.",
                "project" => $project
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function addMemberToProject(Request $request, $id)
    {
        try {
            // Find the project
            $project = Project::findOrFail($id);

            // Check if the user is already a member of the project
            if ($project->members()->where('user_id', $request->userId)->exists()) {
                return response()->json([
                    "status" => false,
                    "message" => "User is already a member of the project."
                ], 400);
            }

            // Attach the user to the project
            $project->members()->attach($request->userId);
            $project = Project::with(['members','kanbans.kanbanLists.tickets.comments'])->withCount('members')->findOrFail($id);

            return response()->json([
                "status" => true,
                "message" => "User added to the project successfully.",
                "project" => $project
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function removeMemberFromProject(Request $request, $id)
    {


        try {

            $project = Project::findOrFail($id);

            if (!$project->members()->where('user_id', $request->userId)->exists()) {
                return response()->json([
                    "status" => false,
                    "message" => "User is not a member of the project."
                ], 400);
            }
            $kanbans= Kanban::with('members')->get();
            foreach ($kanbans as $kanban) {
                $kanban->members()->detach($request->userId);
            }

            // Attach the user to the project
            $project->members()->detach($request->userId);

            $project = Project::with(['members','kanbans.kanbanLists.tickets.comments'])->withCount('members')->findOrFail($id);

            return response()->json([
                "status" => true,
                "message" => "User removed from the project successfully.",
                "project" => $project
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }
}
