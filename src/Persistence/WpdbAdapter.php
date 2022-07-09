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

    /** region table management */
    /**
     * Create the database tables, that are needed to store the desired data.
     */
    public function createTables(): bool
    {
        $result = true;

        // chained konjunction
        // if one of the exec()-calls (table creations) fail, the others won't be attempted
        $result = $this->createTableTeams()
        && $this->createTableLeagueMetadata()
        && $this->createTableGames()
        && $this->createTableTabScores();

        return $result;
    }

    /**
     * Drop the database tables. Useful for uninstallation.
     */
    public function dropTables(): bool
    {
        $result = true;
        $tables = [
            "teams",
            "leaguemetadata",
            "games",
            "tabscores",
        ];

        foreach ($tables as $table) {
            // if one fails, the others will still be attempted, but the result
            // stays false
            $result = $this->wpdb->query(
                "DROP TABLE IF EXISTS {$this->wpdb->prefix}h4ac_{$table}"
            ) && $result;
        }

        return $result;
    }
    /** endregion */

    /**
     * Create the teams table.
     */
    private function createTableTeams(): bool
    {
        return (bool) $this->wpdb->query(
            <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}h4ac_teams (
				id INTEGER PRIMARY KEY AUTO_INCREMENT,
				internalName VARCHAR(255) NOT NULL,
				identificators VARCHAR(255) NOT NULL,
				leagueUrl VARCHAR(255) NULL,
				cupUrl VARCHAR(255) NULL
			);
SQL
        );
    }

    /**
     * Create the metadata table.
     */
    private function createTableLeagueMetadata(): bool
    {
        return (bool) $this->wpdb->query(
            <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}h4ac_leaguemetadata (
				teamid INTEGER NOT NULL,
				id INTEGER PRIMARY KEY AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				sname VARCHAR(255) NOT NULL,
				headline1 VARCHAR(255) NOT NULL,
				headline2 VARCHAR(255) NOT NULL,
				actualized VARCHAR(255) NOT NULL,
				repUrl VARCHAR(255) NOT NULL,
                scoreShownPerGame BOOLEAN NOT NULL,
				CONSTRAINT fk_leaguemetadata_team
					FOREIGN KEY (teamid) REFERENCES {$this->wpdb->prefix}h4ac_teams(id)
					ON DELETE CASCADE ON UPDATE CASCADE
			);
SQL
        );
    }

    /**
     * Create the games table.
     */
    private function createTableGames(): bool
    {
        return (bool) $this->wpdb->query(
            <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}h4ac_games (
				metadataid INTEGER NOT NULL,
				id INTEGER PRIMARY KEY AUTO_INCREMENT,
				gID VARCHAR(255) NOT NULL,
				sGID VARCHAR(255) NULL DEFAULT NULL,
				gNo VARCHAR(255) NOT NULL,
				live BOOLEAN NOT NULL,
				gToken VARCHAR(255) NULL DEFAULT NULL,
				gAppid VARCHAR(255) NOT NULL,
				gDate VARCHAR(255) NOT NULL,
				gWDay VARCHAR(255) NOT NULL,
				gTime VARCHAR(255) NOT NULL,
				gGymnasiumID VARCHAR(255) NOT NULL,
				gGymnasiumNo VARCHAR(255) NOT NULL,
				gGymnasiumName VARCHAR(255) NOT NULL,
				gGymnasiumPostal VARCHAR(255) NOT NULL,
				gGymnasiumTown VARCHAR(255) NOT NULL,
				gGymnasiumStreet VARCHAR(255) NOT NULL,
				gHomeTeam VARCHAR(255) NOT NULL,
				gGuestTeam VARCHAR(255) NOT NULL,
				gHomeGoals INT NULL DEFAULT NULL,
				gGuestGoals INT NULL DEFAULT NULL,
				gHomeGoals_1 INT NULL DEFAULT NULL,
				gGuestGoals_1 INT NULL DEFAULT NULL,
				gHomePoints INT NULL DEFAULT NULL,
				gGuestPoints INT NULL DEFAULT NULL,
				gComment VARCHAR(255) NOT NULL,
				gGroupsortTxt VARCHAR(255) NOT NULL,
				gReferee VARCHAR(255) NOT NULL,
				robotextstate VARCHAR(255) NOT NULL,
				CONSTRAINT fk_games_leaguemetadata
					FOREIGN KEY (metadataid) REFERENCES {$this->wpdb->prefix}h4ac_leaguemetadata(id)
					ON UPDATE CASCADE ON DELETE CASCADE
			);
SQL
        );
    }

    /**
     * Create the games table.
     */
    private function createTableTabScores(): bool
    {
        return (bool) $this->wpdb->query(
            <<<SQL
			CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}h4ac_tabscores (
				metadataid INTEGER NOT NULL,
                id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
				tabScore INT NOT NULL,
				tabTeamID VARCHAR(255) NOT NULL,
				tabTeamname VARCHAR(255) NOT NULL,
				liveTeam BOOLEAN NOT NULL,
				numPlayedGames INT NOT NULL,
				numWonGames INT NOT NULL,
				numEqualGames INT NOT NULL,
				numLostGames INT NOT NULL,
				numGoalsShot INT NOT NULL,
				numGoalsGot INT NOT NULL,
				pointsPlus INT NOT NULL,
				pointsMinus INT NOT NULL,
				pointsPerGame10 VARCHAR(255) NOT NULL,
				numGoalsDiffperGame VARCHAR(255) NOT NULL,
				numGoalsShotperGame VARCHAR(255) NOT NULL,
				posCriterion VARCHAR(255) NOT NULL,
				CONSTRAINT fk_tabscore_metadata
					FOREIGN KEY (metadataid) REFERENCES {$this->wpdb->prefix}h4ac_leaguemetadata(id)
					ON UPDATE CASCADE ON DELETE CASCADE
			);
SQL
        );
    }
}
