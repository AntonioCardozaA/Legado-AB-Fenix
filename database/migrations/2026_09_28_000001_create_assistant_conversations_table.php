<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_message_at']);
        });

        Schema::table('assistant_messages', function (Blueprint $table): void {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('user_id')
                ->constrained('assistant_conversations')
                ->cascadeOnDelete();

            $table->index(['conversation_id', 'created_at']);
        });

        DB::table('assistant_messages')
            ->select('user_id')
            ->distinct()
            ->orderBy('user_id')
            ->get()
            ->each(function ($row): void {
                $firstMessage = DB::table('assistant_messages')
                    ->where('user_id', $row->user_id)
                    ->orderBy('id')
                    ->first(['content', 'created_at']);

                $lastMessage = DB::table('assistant_messages')
                    ->where('user_id', $row->user_id)
                    ->orderByDesc('id')
                    ->first(['created_at']);

                $conversationId = DB::table('assistant_conversations')->insertGetId([
                    'user_id' => $row->user_id,
                    'title' => $this->titleFrom((string) ($firstMessage->content ?? 'Chat operativo')),
                    'last_message_at' => $lastMessage->created_at ?? now(),
                    'created_at' => $firstMessage->created_at ?? now(),
                    'updated_at' => $lastMessage->created_at ?? now(),
                ]);

                DB::table('assistant_messages')
                    ->where('user_id', $row->user_id)
                    ->whereNull('conversation_id')
                    ->update(['conversation_id' => $conversationId]);
            });
    }

    public function down(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table): void {
            $table->dropForeign(['conversation_id']);
            $table->dropIndex(['conversation_id', 'created_at']);
            $table->dropColumn('conversation_id');
        });

        Schema::dropIfExists('assistant_conversations');
    }

    private function titleFrom(string $content): string
    {
        $title = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?: '');

        return Str::limit($title !== '' ? $title : 'Chat operativo', 80, '');
    }
};
