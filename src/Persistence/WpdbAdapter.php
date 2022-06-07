<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress\Persistence;

use Tobb10001\H4aIntegration\Models\LeagueData;
use Tobb10001\H4aIntegration\Persistence\PersistenceInterface;
use wpdb;

class WpdbAdapter implements PersistenceInterface
{
    private wpdb $wpdb;

    /**
     * If needed, the wpdb-instance used by this Object can be overriden. By
     * default, i.e. $wpdb = null, the global $wpdb-object will be used.
     * @param ?wpdb $wpdbParam A reference to a $wpdb object.
     */
    public function __construct(?wpdb $wpdbParam = null)
    {
        global $wpdb;
        $this->wpdb = $wpdbParam ?? $wpdb;
    }

    /** region PersistenceInterface */
    /**
     * {@inheritdoc}
     */
    public function getTeams(): array
    {
        return []; // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function replaceLeagueData(int $teamid, LeagueData $leagueData): bool
    {
        return false; // TODO
    }
    /** endregion */

    /** region Table Creation */
    /**
     * Construct the "CREATE TABLE" query for the teams table.
     */
    private function queryCreateTableTeams(): string
    {
        return <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}teams (
				id INTEGER PRIMARY KEY AUTO_INCREMENT,
				internalName VARCHAR NOT NULL,
				identificators VARCHAR NOT NULL,
				leagueUrl VARCHAR NULL,
				cupUrl VARCHAR NULL
			);
SQL;
    }

    /**
     * Create the teams table.
     */
    private function createTableTeams(): bool
    {
        return (bool) $this->wpdb->query(self::queryCreateTableTeams());
    }

    /**
     * Construct the "CREATE TABLE" query for the league data table.
     */
    private function queryCreateTableLeagueMetadata(): string
    {
        return <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}leaguemetadata (
				teamid INTEGER NOT NULL,
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				name VARCHAR NOT NULL,
				sname VARCHAR NOT NULL,
				headline1 VARCHAR NOT NULL,
				headline2 VARCHAR NOT NULL,
				actualized VARCHAR NOT NULL,
				repUrl VARCHAR NOT NULL,
				scoreShownPerGame NOT NULL,
				CONSTRAINT fk_team
					FOREIGN KEY (teamid) REFERENCES {$this->wpdb->prefix}team(id)
					ON UPDATE CASCADE ON DELETE CASCADE
			);
SQL;
    }

    /**
     * Create the metadata table.
     */
    private function createTableLeagueMetadata(): bool
    {
        return (bool) $this->wpdb->query(self::queryCreateTableLeagueMetadata());
    }

    /**
     * Construct the "CREATE TABLE" query for the games table.
     */
    private function queryCreateTableGames(): string
    {
        return <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}games (
				metadataid INTEGER NOT NULL,
				id INTEGER PRIMARY KEY AUTO_INCREMENT,
				gID VARCHAR NOT NULL,
				sGID VARCHAR NULL DEFAULT NULL,
				gNo VARCHAR NOT NULL,
				live BOOLEAN NOT NULL,
				gToken VARCHAR NULL DEFAULT NULL,
				gAppid VARCHAR NOT NULL,
				gDate VARCHAR NOT NULL,
				gWDay VARCHAR NOT NULL,
				gTime VARCHAR NOT NULL,
				gGymnasiumID VARCHAR NOT NULL,
				gGymnasiumNo VARCHAR NOT NULL,
				gGymnasiumName VARCHAR NOT NULL,
				gGymnasiumPostal VARCHAR NOT NULL,
				gGymnasiumTown VARCHAR NOT NULL,
				gGymnasiumStreet VARCHAR NOT NULL,
				gHomeTeam VARCHAR NOT NULL,
				gGuestTeam VARCHAR NOT NULL,
				gHomeGoals INT NULL DEFAULT NULL,
				gGuestGoals INT NULL DEFAULT NULL,
				gHomeGoals_1 INT NULL DEFAULT NULL,
				gGuestGoals_1 INT NULL DEFAULT NULL,
				gHomePoints INT NULL DEFAULT NULL,
				gGuestPoints INT NULL DEFAULT NULL,
				gComment VARCHAR NOT NULL,
				gGroupsortTxt VARCHAR NOT NULL,
				gReferee VARCHAR NOT NULL,
				robotextstate VARCHAR NOT NULL,
				CONSTRAINT fk_metadata
					FOREIGN KEY (metadataid) REFERENCES {$this->wpdb->prefix}metadata(id)
					ON UPDATE CASCADE ON DELETE CASCADE
			);
SQL;
    }

    /**
     * Create the games table.
     */
    private function createTableGames(): bool
    {
        return (bool) $this->wpdb->query(self::queryCreateTableGames());
    }

    /**
     * Construct the "CREATE TABLE" query for the tabscores table.
     */
    private function queryCreateTableTabScores(): string
    {
        return <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}tabscores (
				metadataid INTEGER NOT NULL,
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				tabScore INT NOT NULL,
				tabTeamID VARCHAR NOT NULL,
				tabTeamname VARCHAR NOT NULL,
				liveTeam BOOLEAN NOT NULL,
				numPlayedGames INT NOT NULL,
				numWonGames INT NOT NULL,
				numEqualGames INT NOT NULL,
				numLostGames INT NOT NULL,
				numGoalsShot INT NOT NULL,
				numGoalsGot INT NOT NULL,
				pointsPlus INT NOT NULL,
				pointsMinus INT NOT NULL,
				pointsPerGame10 VARCHAR NOT NULL,
				numGoalsDiffperGame VARCHAR NOT NULL,
				numGoalsShotperGame VARCHAR NOT NULL,
				posCriterion VARCHAR NOT NULL,
				CONSTRAINT fk_metadata
					FOREIGN KEY (metadataid) REFERENCES {$this->wpdb->prefix}metadata(id)
					ON UPDATE CASCADE ON DELETE CASCADE
			);
SQL;
    }

    /**
     * Create the games table.
     */
    private function createTableTabScores(): bool
    {
        return (bool) $this->wpdb->query(self::queryCreateTableTabScores());
    }

    /**
     * Create the database tables, that are needed to store the desired data.
     */
    public function createTables(): bool
    {
        $result = true;

        $this->wpdb->query("START TRANSACTION;");
        // chained konjunction
        // if one of the exec()-calls (table creations) fail, the others won't be attempted
        $result = $this->createTableTeams()
        && $this->createTableLeagueMetadata()
        && $this->createTableGames()
        && $this->createTableTabScores();

        $this->wpdb->query($result ? "COMMIT;" : "ROLLBACK;");

        return $result;
    }
    /** endregion */
}
