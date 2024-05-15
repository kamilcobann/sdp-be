<?php

namespace App\Http\Controllers;

use App\Models\Kanban;
use App\Models\KanbanList;
use Exception;
use Illuminate\Http\Request;

class KanbanListController extends Controller
{
    public function createKanbanList(Request $request, int $kanbanId)
    {
        try {
            $kanban = Kanban::findOrFail($kanbanId);
            if ($kanban->kanbanLists()->whereTitle($request->title)->exists()) {
                return response()->json([
                    "status" => false,
                    "message" => "You cannot create a list with an existing title."
                ], 400);
            }
            $kanban->kanbanLists()->create(
                $request->validate([
                    "title" => "required|string|max:255"
                ])
            );

            $kanban = Kanban::with('kanbanLists.tickets.comments')->whereId($kanbanId)->get();

            return response()->json([
                "status" => true,
                "message" => "Kanban List created successfully.",
                "kanban" => $kanban
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function getKanbanLists(int $kanbanId)
    {
        try {
            if (!Kanban::findOrFail($kanbanId)) {
            }
            $kanbanList = KanbanList::where('by_kanban_id',$kanbanId)->with('tickets')->get();
            return response()->json([
                "status" => true,
                "message" => "Kanban Lists retrieved successfully.",
                "kanban_lists" => $kanbanList
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }


    public function getKanbanListById(int $kanbanId, int $kanbanListId)
    {
        try {
            if (!Kanban::findOrFail($kanbanId) || !KanbanList::findOrFail($kanbanListId)) {

            }
            $kanbanList = KanbanList::whereId($kanbanListId)->where('by_kanban_id',$kanbanId)->with('tickets.comments')->first();
            return response()->json([
                "status" => true,
                "message" => "Kanban List retrieved successfully",
                "kanban_list" => $kanbanList,

            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function updateKanbanList(Request $request, int $kanbanId,int $kanbanListId){
        try {
            $kanbanList = KanbanList::whereId($kanbanListId)->where('by_kanban_id',$kanbanId)->with('tickets.comments')->first();
            $kanbanList->title = $request->title ?? $kanbanList->title;
            $kanbanList->save();

            return response()->json([
                "status" => true,
                "message" => "Kanban List updated successfully",
                "kanban_list" => $kanbanList
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function deleteKanbanList(int $kanbanId,int $kanbanListId){
        try {
            $kanbanList = KanbanList::whereId($kanbanListId)->where('by_kanban_id',$kanbanId)->first();
            $kanbanList->delete();

            return response()->json([
                "status" => true,
                "message" => "Kanban List deleted successfully"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }
}
