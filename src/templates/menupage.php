<?php

declare(strict_types=1);

use Tobb10001\H4aIntegration\Models\Team;

/**
 * Render the Menupage.
 *
 * @param array<Team> $teams Teams that are currently available.
 * @param string $nonceActionName Name of the action for the nonce field.
 * @param string $nonceFieldName Name of the nonce field to be used.
 *
 */
function h4ac_print_menupage(array $teams, string $nonceActionName, string $nonceFieldName)
{
    ?>
    <div class='wrap'>
    <h1>Handball4All Client</h1>

    <section>
        <h2>Teams</h2>

    <?php if (!$teams) : ?>
        <p>Zum aktuellen Zeitpunkt befinden sich keine Teams in der Datenbank.</p>
    <?php else : ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Name (intern)</th>
                    <th>Identifikatoren</th>
                    <th>Link (Liga)</th>
                    <th>Link (Pokal)</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teams as $team) : ?>
                    <tr>
                        <td><?= $team->internalName ?></td>
                        <td><?= $team->identificatorStr() ?></td>
                        <td><?= $team->leagueUrl ?></td>
                        <td><?= $team->cupUrl ?></td>
                        <td>
                            <form method="POST">
                                <?php wp_nonce_field($nonceActionName, $nonceFieldName); ?>
                                <input type="hidden" name="action" value="h4ac-delete-team" />
                                <input type="hidden" name="id" value="<?= $team->id ?>" />
                                <input type="submit" value="Löschen" class="button-secondary" />
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Hinzufügen / Bearbeiten</h3>

    <!-- TODO: add functionality to this button -->
    <button style="display: none;">Neues Team erstellen.</button>

    <form method="POST">
        <?php wp_nonce_field($nonceActionName, $nonceFieldName); ?>
        <input type="hidden" name="action" value="h4ac-edit-team">
        <input type="hidden" name="id" value="-1">
        <fieldset style="border: solid black 2px; padding: 5px;">
            <legend id="new-edit-indicator">Neues Team erstellen</legend>
            <p>
                <strong>Vereinsinterner Name</strong><br>
                Dieser Name wird in Widgets zu sehen sein. Sollte für Besucher
                verständlich sein.<br>
                <input type="text" name="internalName">
            </p>
            <p>
                <strong>Identifikatoren</strong><br>
                Bezeichnungen der Mannschaft, die auf Handball4All verwendet werden.
                Werden vom Programm intern verwendet, um die Mannschaft in den
                geholten Daten zuzuordnen. Mehrere Werte können durch Kommas
                getrennt angegeben werden.<br>
                <input type="text" name="identificators">
            </p>
            <p>
                <strong>Link (Liga)</strong><br>
                Link der zur Mannschaftsansicht in der jeweiligen Liga auf
                Handball4All führt.<br>
                <input type="text" name="leagueUrl">
            </p>
            <p>
                <strong>Link (Pokal)</strong><br>
                Link der zur Mannschaftsansicht im jeweiligen Pokal auf
                Handball4All führt.<br>
                <input type="text" name="cupUrl">
            </p>
        <input type="submit" class="button-primary" value="Speichern">
        </fieldset>
    </form>

    </section>

    </div>
    <?php
}
