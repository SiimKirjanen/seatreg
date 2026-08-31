<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class SeatregMigrationsRunner {
    /**
     * Every migration that has been added, in the order they have to run.
     *
     * A migration is a class with a static run() method. They all run again whenever
     * SEATREG_TRIGGER_MIGRATIONS is raised, so a migration has to be written in a way that running
     * it a second time does nothing. A migration that can not do its work logs the reason and is
     * not tried again, so a fix for it belongs in a release that raises SEATREG_TRIGGER_MIGRATIONS.
     */
    private static $migrations = array(
        'SeatregEncryptStripeCredentialsMigration',
        'SeatregBackfillStripeWebhookUrlMigration',
    );

    /**
     *
     * Run the migrations that have not been run with the current SEATREG_TRIGGER_MIGRATIONS value.
     *
     * This has to run after the tables are up to date, because a migration can need a column that
     * was just added or widened.
     *
     */
    public static function run() {
        if( get_option('seatreg_migrations_version') === SEATREG_TRIGGER_MIGRATIONS ) {
            return;
        }

        foreach( self::$migrations as $migration ) {
            call_user_func( array($migration, 'run') );
        }

        update_option( 'seatreg_migrations_version', SEATREG_TRIGGER_MIGRATIONS );
    }
}
