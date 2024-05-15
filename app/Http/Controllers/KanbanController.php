<?php

namespace App\Http\Controllers;

use App\Models\Kanban;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use libphonenumber\Leniency\Valid;

class KanbanController extends Controller
{

    public function createKanban(Request $request, int $projectId)
    {


        $validator = Validator::make($request->all(), [
            "name" => "string|required|max:255",
            "is_active" => "boolean",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                "status" => false,
                "message" => $errors
            ], 400);
        }

        try {
            if (!$kanban = Kanban::create([
                'name' => $request->name,
                'is_active' => $request->is_active ? $request->is_active : true,
                'by_user_id' => $request->user()->id,
                'by_project_id' => $projectId
            ])) {
                return response()->json([
                    "status" => false,
                    "message" => "Kanban creation failed."
                ], 400);
            }

            return response()->json([
                "status" => true,
                "message" => "Kanban created.",
                "kanban" => $kanban
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function getAllKanbans(Request $request)
    {

        try {
            $kanbans = Kanban::with(['members','relatedProject','kanbanLists.tickets.comments'])->get();

            return response()->json([
                "status" => true,
                "message" => "All kanbans are successfully retrieved.",
                "kanbans" => $kanbans
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function getAllKanbansOfUser(Request $request)
    {
        try {
            $kanbans = Kanban::where('by_user_id', auth()->id())->with(['members','relatedProject','kanbanLists.tickets.comments'])->get();

            return response()->json([
                "status" => true,
                "message" => "All kanbans are successfully retrieved.",
                "kanbans" => $kanbans
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function getAllKanbansOfProject(Request $request, int $projectId)
    {
        try {
            $kanbans = Kanban::where('by_project_id', $projectId)->with(['members','relatedProject','kanbanLists.tickets.comments'])->get();

            return response()->json([
                "status" => true,
                "message" => "All kanbans are successfully retrieved.",
                "kanbans" => $kanbans
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function getKanbanById($id)
    {

        try {
           $kanban = Kanban::with(['members','kanbanLists.tickets.comments','relatedProject'])->whereId($id)->first();
            return response()->json([
                "status" => true,
                "message" => "Kanban successfully retrieved.",
                "kanban" => $kanban
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 404);
        }
    }

    public function updateKanban(Request $request, int $kanbanId)
    {

        try {
            $kanban = Kanban::with(['members','kanbanLists.tickets.comments','relatedProject'])->whereId($kanbanId)->first();

            $validator = Validator::make($request->all(), [
                "name" => "string|max:255",
                "is_active" => "boolean"
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json([
                    "status" => false,
                    "message" => $errors,
                ], 400);
            }

            $kanban->name = $request->name ?? $kanban->name;
            $kanban->is_active = $request->is_active ?? $kanban->is_active;

            $kanban->save();

            return response()->json([
                "status" => true,
                "message" => "Kanban updated successfully",
                "kanban" => $kanban
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function deleteKanban(Request $request, $kanbanId)
    {
        try {
            $kanban = Kanban::findOrFail($kanbanId);
            $kanban->delete();
            return response()->json([
                "status" => true,
                "message" => "Kanban deleted."
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function addMemberToKanban(Request $request, $kanbanId)
    {
        try {
            $kanban = Kanban::findOrFail($kanbanId);

            $project = Project::findOrFail($kanban->by_project_id);

            if ($project->members()->where('user_id', $request->userId)->exists()) {
                if ($kanban->members()->where('user_id', $request->userId)->exists()) {
                    return response()->json([
                        "status" => false,
                        "message" => "User is already a member of this kanban"
                    ],400);
                }
                $kanban->members()->attach($request->userId);

                $kanban = Kanban::with(['members','kanbanLists.tickets.comments','relatedProject'])->whereId($kanbanId)->get();
                return response()->json([
                    "status" => true,
                    "message" => "User added to kanban successfully",
                    "kanban" => $kanban
                ],200);
            }

            return response()->json([
                "status" => false,
                "message" => "User is not a member of this project"
            ],400);

        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }


    public function removeMemberFromKanban(Request $request, $kanbanId){
        try {
            $kanban = Kanban::findOrFail($kanbanId);
            $project = Project::findOrFail($kanban->by_project_id);
            if($kanban->by_user_id != $request->user()->id){
                return response()->json([
                    "status" => false,
                    "message" => "You are not authorized to access this kanban"
                ],403);
            }

            if(!$project->members()->where('user_id',$request->userId)->exists()){
                return response()->json([
                    "status" => false,
                    "message" => "User is not a member of the project."
                ], 400);
            }

            $kanban->members()->detach($request->userId);
            $kanban = Kanban::with(['members','kanbanLists.tickets.comments','relatedProject'])->whereId($kanbanId)->get();

            return response()->json([
                "status" => true,
                "message" => "User removed from kanban successfully",
                "kanban" => $kanban
            ],200);

        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }
}
