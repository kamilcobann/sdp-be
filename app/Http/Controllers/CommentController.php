<?php

namespace App\Http\Controllers;
// use Ahmedash95\Sentimento\Facade\Sentimento;
use Google\Cloud\Core\ServiceBuilder;
use Sentiment\Analyzer;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function createComment(Request $request, $ticketId)
    {
        $validator = Validator::make($request->all(), [
            "content" => "string|required|max:255"
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                "status" => false,
                "message" => $errors
            ], 400);
        }

        try {
            if (!$comment = $request->user()->createdComments()->create([
                "content" => $request->content,
                "by_ticket_id" => $ticketId
            ])) {

                return response()->json([
                    "status" => false,
                    "message" => "Comment creation failed."
                ], 400);
            }

            $ticket = Ticket::findOrFail($ticketId);
            $ticket->comments()->save($comment);
            return response()->json([
                "status" => true,
                "message" => "Comment created.",
                "comment" => $comment
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function getAllComments()
    {
        $analyzer = new Analyzer();

        try {
            $comments = Comment::with(['ticket', 'owner'])->get();
            $commentsWithAnalysis = $comments->map(function ($comment) use ($analyzer) {
                return [
                    "comment" => $comment,
                    "analysis" => $analyzer->getSentiment($comment->content)
                ];
            });

            return response()->json([
                "status" => true,
                "message" => "Comments are successfully retrieved.",
                "comments" => $commentsWithAnalysis
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function getAllCommentsOfTicket($ticketId)
    {
        $analyzer = new Analyzer();

        try {
            $comments = Comment::where('by_ticket_id', $ticketId)->with(['ticket', 'owner'])->get();
            $commentsWithAnalysis = $comments->map(function ($comment) use ($analyzer) {
                return [
                    "comment" => $comment,
                    "analysis" => $analyzer->getSentiment($comment->content)
                ];
            });
            return response()->json([
                "status" => true,
                "message" => "Comments retrieved.",
                "comments" => $commentsWithAnalysis
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function getAllCommentsOfUser(Request $request)
    {
        $analyzer = new Analyzer();

        try {
            $comments = Comment::where('by_user_id', $request->user()->id)->with(['ticket', 'owner'])->get();
            $commentsWithAnalysis = $comments->map(function ($comment) use ($analyzer) {
                return [
                    "comment" => $comment,
                    "analysis" => $analyzer->getSentiment($comment->content)
                ];
            });
            return response()->json([
                "status" => true,
                "message" => "Comments are successfully retrieved.",
                "comments" => $commentsWithAnalysis
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function getCommentById($commentId)
    {
        $analyzer = new Analyzer();

        try {
            $comment = Comment::whereId($commentId)->with(['ticket', 'owner'])->first();
            return response()->json([
                "status" => true,
                "message" => "Comment are successfully retrieved.",
                "comment" => $comment,
                "analysis" => $analyzer->getSentiment($comment->content)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage(),
            ], 400);
        }
    }

    public function updateComment(Request $request, $commentId)
    {
        try {
            $comment = Comment::whereId($commentId)->with(['members', 'kanbans.kanbanLists'])->first();
            $validator = Validator::make($request->all(), [
                "content" => "string|required"
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                return response()->json([
                    "status" => false,
                    "message" => $errors,
                ], 400);
            }

            $comment->content = $request->content;
            $comment->save();

            return response()->json([
                "status" => true,
                "message" => "Comment updated.",
                "comment" => $comment
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }

    public function deleteCommentById($commentId)
    {
        try {
            if (!$comment = Comment::find($commentId)) {
                return response()->json([
                    "status" => false,
                    "message" => "Comment with id: " . $commentId . " not found."
                ], 404);
            }

            $comment->delete();
            return response()->json([
                "status" => true,
                "message" => "Comment deleted."
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "message" => $th->getMessage()
            ], 400);
        }
    }
}
