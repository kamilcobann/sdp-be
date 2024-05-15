<?php

namespace App\Http\Controllers;

use App\Models\Kanban;
use App\Models\KanbanList;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function getTicketById(int $id)
    {

        try {
            $ticket = Ticket::with(['assignedUsers','comments'])->whereId($id)->get();
            return response()->json([
                "status" => true,
                "message" => "Ticket successfully retrieved.",
                "ticket" => $ticket
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 404);
        }
    }

    public function createTicket(Request $request,$kanbanListId)
    {
        $validator = Validator::make($request->all(), [
            "title" => "string|required|max:255",
            "description" => "string|required|max:255"
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                "status" => false,
                "message" => $errors
            ], 400);
        }

        try {

            if (!$ticket = $request->user()->createdTickets()->create([
                "title" => $request->title,
                "description" => $request->description,
                "by_kanban_list_id" => $kanbanListId
            ])) {
                return response()->json([
                    "status" => false,
                    "message" => "Ticket creation failed."
                ], 400);
            }

            $kanbanList = KanbanList::findOrFail($kanbanListId);
            $kanbanList->tickets()->save($ticket);
            return response()->json([
                "status" => true,
                "message" => "Ticket created.",
                "ticket" => $ticket
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }


    public function getAllTickets()
    {
        try {
            $tickets = Ticket::with(['assignedUsers','comments'])->get();
            return response()->json([
                "status" => true,
                "message" => "Tickets successfully retrieved.",
                "tickets" => $tickets
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 404);
        }
    }


    public function getAllTicketsCreatedFromUser(Request $request)
    {
        try {
            $tickets = Ticket::where('by_user_id', $request->user()->id)->with(['assignedUsers','comments'])->get();
            return response()->json([
                "status" => true,
                "message" => "Tickets successfully retrieved.",
                "tickets" => $tickets
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 404);
        }
    }

    public function getAssignedTickets(Request $request)
    {
        try {

            $tickets = Ticket::where('by_user_id', $request->user()->id)->with(['assignedUsers','comments'])->get();
            return response()->json([
                "status" => true,
                "message" => "Tickets successfully retrieved.",
                "tickets" => $tickets
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 404);
        }
    }

    public function deleteTicketById(int $id)
    {
        try {
            if (!$ticket = Ticket::find($id)) {
                return response()->json([
                    "status" => false,
                    "message" => "Ticket with id: " . $id . " not found."
                ], 404);
            }

            $ticket->delete();
            return response()->json([
                "status" => true,
                "message" => "Ticket deleted"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function addMemberToTicket(Request $request, $id)
    {
        try {

            $ticket = Ticket::findOrFail($id);
            $kanbanList = KanbanList::findOrFail($ticket->by_kanban_list_id);
            $kanban = Kanban::findOrFail($kanbanList->by_kanban_id);
            $project =  Project::findOrFail($kanban->by_project_id);
            if ($project->members()->where('user_id', $request->userId)->exists()) {

                if ($ticket->assignedUsers()->where('user_id', $request->userId)->exists()) {
                    return response()->json([
                        "status" => false,
                        "message" => "User is already a member of this ticket"
                    ], 400);
                }

                $ticket->assignedUsers()->attach($request->userId);
                $ticket = Ticket::with(['assignedUsers', 'owner','comments'])->whereId($id)->get();
                return response()->json([
                    "status" => true,
                    "message" => "User added to ticket successfully",
                    "ticket" => $ticket
                ], 200);
            }

            return response()->json([
                "status" => false,
                "message" => "User is not a member of this project"
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function removeMemberFromTicket(Request $request, $id)
    {
        try {

            $ticket = Ticket::findOrFail($id);
            $kanbanList = KanbanList::findOrFail($ticket->by_kanban_list_id);
            $kanban = Kanban::with('owner')->whereId($kanbanList->by_kanban_id)->first();
            // return $kanban->owner->id;
            $project =  Project::findOrFail($kanban->by_project_id);

            if (!$project->members()->where('user_id', $request->userId)->exists()) {
                return response()->json([
                    "status" => false,
                    "message" => "User is not a member of the project."
                ], 400);
            }


            if ($kanban->owner->id != ($request->user()->id)) {
                return response()->json([
                    "status" => false,
                    "message" => "You are not authorized to modify this kanban"
                ], 403);
            }

            $ticket->assignedUsers()->detach($request->userId);
            $ticket = Ticket::with(['assignedUsers', 'owner','comments'])->whereId($id)->get();

            return response()->json([
                "status" => true,
                "message" => "User removed from ticket successfully",
                "ticket" => $ticket
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function updateTicketById(Request $request,$id){
        try{
            $ticket = Ticket::with(['assignedUsers','owner','comments'])->whereId($id)->first();
            $validator = Validator::make($request->all(),[
                "title" => "string|min:0|max:255",
                "description" => "string|min:0|max:255"
            ]);

            if($validator->fails()){
                $errors = $validator->errors();
                return response()->json([
                    "status"=>false,
                    "message"=>$errors,
                ],400);
            }

            $ticket->title = $request->title;
            $ticket->description = $request->description;
            $ticket->by_kanban_list_id = $request->by_kanban_list_id;

            $ticket->save();

            return response()->json([
                "status" => true,
                "message" => "Ticket updated.",
                "ticket" => $ticket
            ]);
        }catch(\Throwable $th){
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }
}
