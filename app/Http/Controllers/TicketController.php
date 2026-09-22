<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\TicketStoreRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\Ticket;
use App\Http\Resources\TicketResource;
use App\Models\TicketReply;
use App\Http\Requests\TicketReplyStoreRequest;
use Exception;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Ticket::query();
            $query->orderBy('created_at', 'desc');
            if($request->search){
                $query->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('title', 'like', '%'.$request->search.'%');
            }
            if ($request->status){
                $query->where('status', $request->status);

            }
            if ($request->priority){
                $query->where('priority', $request->priority);
            }

            if (auth()->user()->role=='user'){
                $query->where('user_id', auth()->user()->id);
            }
            $tickets = $query->get();
            return response()->json([
                'data' => TicketResource::collection($tickets)
            ],200);
        } catch (\Exception $e) {  
            return response()->json([
                'message' => 'Failed to create ticket',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }


    }

    public function store(TicketStoreRequest $request)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $ticket = new Ticket();
            $ticket->user_id = auth()->user()->id;
            $ticket->code = 'TIC-' . rand(10,100);
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->priority = $data['priority'];
            $ticket->save();
            DB::commit();
            return response()->json([
                'message' => 'Ticket created successfully',
                'data' => new TicketResource($ticket)
            ],201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create ticket',
                'data' => null,
                'error' => $e->getMessage()
            ], 500);
        }
        
        

        
    }

    public function show($code)
    {
        try {
            $ticket = Ticket::where('code', $code)->first();
            if(!$ticket){
                return response()->json([
                    'message'=> 'Tiket tidak ditemukan'
                ],404);
            }
            if(auth()->user()->role=='user' && $ticket->user_id != auth()->user()->id){
                return response()->json([
                    'message'=> 'Anda tidak diperbolehkan akses tiket ini'

                ],403);
            }

            return response()->json([
                'message'=> 'Ticket berhasil ditampilkan',
                'data' => new TicketResource($ticket)
            ],200);


        } catch (\Exception $e) {
            return response()->json([
                'message'=> 'Terjadi kesalahan',
                'data' => $e->getMessage()
            ],404);
        }
    }

    public function storeReply(TicketReplyStoreRequest $request,$code)
    {
        $data = $request->validated();
        DB::beginTransaction();
        
        try {
            $ticket = Ticket::where('code',$code)->first();
            if(!$ticket){
                return response()->json([
                    'message'=>'Tiket tidak ditemukan'
                ],404);
            }
            if(auth()->user()->role == 'user' && $ticket->user_id != auth()->user()->id){
                return response()->json([
                    'message'=>'Anda tidak boleh membalas tiket ini'

                ],403);
            }

            $ticketReply = new TicketReply();
            $ticketReply->ticket_id = $ticket->id;
            $ticketReply->user_id = auth()->user()->id;
            $ticketReply->content = $data['content'];
            $ticketReply->save();

            if (auth()->user()->role == 'admin'){
                $ticket->status = $data['status'];
                if ($data['status'] == 'resolved'){
                    $ticket->completed_at = now();
                }
                $ticket->save();

            }
            DB::commit();

            return response()->json([
                'message'=> 'Balasan berhasil ditammbahkan',
                'data'=> new TicketReplyResource($ticketReply)
            ],201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message'=> 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ],500);
        }
    }
        
}
