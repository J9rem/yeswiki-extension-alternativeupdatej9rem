<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
  'ALTERNATIVEUPDATE_ACTIVATE_EXT' => 'Activer',
  'ALTERNATIVEUPDATE_DEACTIVATE_EXT' => 'Désactiver',
  'ALTERNATIVEUPDATE_REINSTALL' => 'Réinstaller',
  'ALTERNATIVEUPDATE_YOUR_PASSWORD' => 'Votre mot de passe :',
  'ALTERNATIVEUPDATE_CONFIRM_PASSWORD' => 'Confirmez votre mot de passe',
  'ALTERNATIVEUPDATE_OTHER_UPDATE_METHOD' => 'Mettre à jour par l\'autre méthode',
  'ALTERNATIVEUPDATE_FINISH_UPDATE' => 'Terminer la mise à jour',
  'ALTERNATIVEUPDATEJ9REM_SHOW_FIELDS' => 'Afficher les champs masqués',
  'ALTERNATIVEUPDATEJ9REM_HIDE_FIELDS' => 'Masquer les champs masqués',

  // actions/EditEntryPartialAction.php
  'AUJ9_EDIT_PARTIAL_ENTRY_ERROR_REGISTER' => 'Une erreur est survenue lors de l\'enregistrement de la fiche',
  'AUJ9_EDIT_ENTRY_PARTIAL_WRONG_PARAMS' => 'Les paramètres de l\'action {{editentrypartials}} semblent avoir été modifiés par un compte non administrateur ou sans passer par le bouton \'composants\'. L\'action est donc désactivée !',
  'AUJ9_ID_PARAM_NOT_EMPTY' => 'le paramètre \'id\' ne doit pas être vide',
  'AUJ9_ID_PARAM_SHOULD_BE_A_FORM' => 'le paramètre \'id\' devrait correspondre à un formulaire existant',
  'AUJ9_ID_PARAM_SHOULD_BE_NUMBER' => 'le paramètre \'id\' devrait être un nombre positif',
  'AUJ9_FIELDS_PARAM_NOT_EMPTY' => 'le paramètre \'fields\' ne doit pas être vide',

  // fields/video.twig
  'VIDEO_LINK_FIELD' => 'Lien vers la vidéo : %{link}',
  
  // services/ActionsBuilderService.php
  'AUJ9_EDIT_ENTRY_PARTIAL_ACTION_FIELDS_LABEL' => 'Champs à modifier',
  'AUJ9_EDIT_ENTRY_PARTIAL_ACTION_LABEL' => 'Modifier partiellement une fiche',
  'AUJ9_BAZARVIDEO_ACTION_LABEL' => 'Affichage des vidéos par bloc',
  'AUJ9_BAZARVIDEO_ACTION_VIDEO_FIELDNAME_LABEL' => 'Champ vidéo',
  'AUJ9_BAZARVIDEO_ACTION_VIDEO_LINK_LABEL' => 'Champ lien',

  // templates/bazar/inputs/date.twig
  'EVENT_IS_LINKED_TO_RECURRENT' => 'Cette évènement est un évènement récurrent lié à la fiche %{link}',

  // templates/bazar/inputs/date.twig
  'EVENT_EVERY_X_DAYS' => 'Tous les X jours',
  'EVENT_EVERY_X_WEEKS' => 'Toutes les X semaines',
  'EVENT_EVERY_X_MONTHS' => 'Tous les X mois',
  'EVENT_EVERY_X_YEARS' => 'Toutes les X années',
  'EVENT_FIRST_Y_OF_MONTH' => 'Le premier Y du mois',
  'EVENT_FORTH_Y_OF_MONTH' => 'Le quatrième Y du mois',
  'EVENT_IS_LINKED_TO_RECURRENT_EDIT' => 'Cette évènement est un évènement récurrent lié à la fiche %{link}.<br/>Toute modification de cette fiche cassera le lien avec l\'évènement de base !',
  'EVENTS_IS_RECURRENT_LABEL' => 'Évènement récurrent ?',
  'EVENTS_HINT' => 'Cet évènement est récurrent. Toute modification de cette fiche entraîne la modification '
    .'voire suppression des évènements liés.<br/> '
    .'Les évènements liés ne seront supprimés que si vous sauvegardez cette fiche en retirant la récurrence (c\'est-à-dire en décochant la case ci-dessus).',
    'EVENT_LAST_Y_OF_MONTH' => 'Le dernier Y du mois',
  'EVENT_NB_MAX_REPETITIONS' => 'Nombre maximum de répétitions :',
  'EVENT_NTH_OF_MONTH' => 'Chaque Y du mois',
  'EVENTS_REPETITION_PERIOD' => 'Répétition :',
  'EVENTS_REPETITION_STEP' => 'X =',
  'EVENT_SECOND_Y_OF_MONTH' => 'Le second Y du mois',
  'EVENT_THIRD_Y_OF_MONTH' => 'Le troisième Y du mois',
  'EVENTS_WHEN_IN_MONTH' => 'A quel moment du mois ?',

  // templates/edit-entry-partial-action.twig
  'AUJ9_EDIT_PARTIAL_NO_ENTRY' => 'Aucune fiche du formulaire <code>%{form}</code> n\'est modifiable pour votre compte utilisateur',
  'AUJ9_EDIT_PARTIAL_SELECT_ENTRY' => 'Fiche sélectionée',
];
