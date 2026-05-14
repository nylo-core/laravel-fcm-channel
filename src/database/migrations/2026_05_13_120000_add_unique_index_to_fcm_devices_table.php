<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueIndexToFcmDevicesTable extends Migration
{
    /**
     * Index name kept short so MySQL's 64-char identifier limit is never an issue.
     */
    private string $indexName = 'fcm_devices_token_notifyable_unique';

    /**
     * Run the migrations.
     *
     * If existing data contains duplicate rows for the same
     * (fcm_token, notifyable_id, notifyable_type) tuple, this migration will
     * fail. Deduplicate manually before running.
     *
     * @return void
     */
    public function up()
    {
        $duplicates = DB::table('fcm_devices')
            ->whereNotNull('fcm_token')
            ->select('fcm_token', 'notifyable_id', 'notifyable_type', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('fcm_token', 'notifyable_id', 'notifyable_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Cannot add unique index to fcm_devices: found %d (fcm_token, notifyable_id, notifyable_type) tuples with duplicate rows. '.
                'Deduplicate before re-running this migration. To list duplicates: '.
                'SELECT fcm_token, notifyable_id, notifyable_type, COUNT(*) FROM fcm_devices '.
                'WHERE fcm_token IS NOT NULL GROUP BY fcm_token, notifyable_id, notifyable_type HAVING COUNT(*) > 1;',
                $duplicates->count()
            ));
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // fcm_token is TEXT — MySQL/MariaDB require a prefix length to index it.
            // 191 chars is the safe ceiling under the legacy 767-byte key limit
            // (utf8mb4 = 4 bytes/char). FCM tokens are random and high-entropy,
            // so prefix collisions on 191 chars are astronomically unlikely.
            DB::statement(sprintf(
                'ALTER TABLE fcm_devices ADD UNIQUE %s (fcm_token(191), notifyable_id, notifyable_type)',
                $this->indexName
            ));

            return;
        }

        Schema::table('fcm_devices', function (Blueprint $table) {
            $table->unique(
                ['fcm_token', 'notifyable_id', 'notifyable_type'],
                $this->indexName
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcm_devices', function (Blueprint $table) {
            $table->dropUnique($this->indexName);
        });
    }
}
