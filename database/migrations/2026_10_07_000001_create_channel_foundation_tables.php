<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared channel foundation used by the SMS, WhatsApp and AI Assistant addons.
 * Email remains on its existing pipeline; these tables add a generic,
 * provider-agnostic model for additional channels.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable(); // null = platform account
            $table->string('channel', 32);          // sms, whatsapp
            $table->string('provider', 64);         // twilio, meta, ...
            $table->string('name');
            $table->string('status', 32)->default('active'); // active, inactive, pending
            $table->boolean('is_platform')->default(false);
            $table->string('from_address')->nullable(); // phone number / sender id / wa phone number id
            $table->string('external_id')->nullable();  // messaging service sid, waba id, phone number id
            $table->text('credentials')->nullable();    // encrypted JSON
            $table->json('settings')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'channel']);
            $table->index(['channel', 'provider']);
            $table->index('status');
        });

        Schema::create('channel_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('channel', 32);
            $table->string('address', 191); // phone number / wa id / email
            $table->string('name')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('consent')->default(true);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'channel', 'address'], 'channel_contacts_unique');
            $table->index(['customer_id', 'channel']);
        });

        Schema::create('channel_threads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('channel', 32);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('status', 32)->default('open'); // open, closed, snoozed, spam
            $table->unsignedInteger('unread_count')->default(0);
            $table->boolean('is_starred')->default(false);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->json('tags')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'channel']);
            $table->index(['customer_id', 'status']);
            $table->index('last_message_at');
        });

        Schema::create('channel_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('thread_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('channel', 32);
            $table->string('direction', 8)->default('out'); // in, out
            $table->string('provider_message_id')->nullable();
            $table->string('from_address', 191)->nullable();
            $table->string('to_address', 191)->nullable();
            $table->text('body')->nullable();
            $table->json('media')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 32)->default('queued'); // queued, sent, delivered, read, failed, received
            $table->string('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'channel']);
            $table->index(['thread_id']);
            $table->index('provider_message_id');
            $table->index('status');
        });

        Schema::create('channel_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32)->nullable();
            $table->string('provider', 64)->nullable();
            $table->string('event_type')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('processed')->default(false);
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'processed']);
            $table->index('created_at');
        });

        Schema::create('channel_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('channel', 32);
            $table->string('provider', 64)->nullable();
            $table->unsignedInteger('units')->default(1);
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'channel']);
            $table->index('created_at');
        });

        // Channel awareness on the existing campaigns table (email stays default).
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('campaigns', 'channel')) {
                    $table->string('channel', 32)->default('email')->after('type');
                    $table->index('channel');
                }
                if (!Schema::hasColumn('campaigns', 'channel_account_id')) {
                    $table->unsignedBigInteger('channel_account_id')->nullable()->after('channel');
                }
                if (!Schema::hasColumn('campaigns', 'channel_config')) {
                    $table->json('channel_config')->nullable()->after('channel_account_id');
                }
            });
        }

        // Phone number for subscribers so SMS/WhatsApp campaigns can target lists.
        if (Schema::hasTable('list_subscribers') && !Schema::hasColumn('list_subscribers', 'phone')) {
            Schema::table('list_subscribers', function (Blueprint $table) {
                $table->string('phone', 191)->nullable()->after('email');
                $table->index('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('list_subscribers') && Schema::hasColumn('list_subscribers', 'phone')) {
            Schema::table('list_subscribers', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }

        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                foreach (['channel', 'channel_account_id', 'channel_config'] as $column) {
                    if (Schema::hasColumn('campaigns', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('channel_usage_logs');
        Schema::dropIfExists('channel_webhook_events');
        Schema::dropIfExists('channel_messages');
        Schema::dropIfExists('channel_threads');
        Schema::dropIfExists('channel_contacts');
        Schema::dropIfExists('channel_accounts');
    }
};
