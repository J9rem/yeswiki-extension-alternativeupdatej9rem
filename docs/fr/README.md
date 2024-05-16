# Documentation de l'extension `alternativeupdatej9rem`

Cette extension permet de mettre à disposition un système de mise à jour des extensions non officielles fournies par [j9rem](https://github.com/J9rem) tout en continuant de recevoir les mises à jour de [`YesWiki`](https://yeswiki.net) et des extensions officielles via le dépôt officielle.

## Contenu de l'extension

### Correctifs

 - l'extension corrige un souci sur les metadonnées des pages qui peuvent être créées en base de données, même si l'utilisateur n'est pas connecté (nécessaire pour `doryphore 4.4.1` et `doryphore 4.4.2`)
 - l'extension corrige une faille de sécurité sur les droits de lecture pour le handler `lmsraw`
 - l'extension propose des correctifs multiples pour `doryphore 4.4.2`
   - correctif sur les routes api pour supprimer une page
   - création d'un `PageController`
   - correction d'un souci avec les champs liés
   - correction d'une erreur de frappe dans ICALFormatter et amélioration du rendu au format ICS
 - l'extension propose des correctifs multiples pour `doryphore 4.4.3` et `4.4.4`
   - correction du comportement des captcha
   - amélioration du cache du `FormManager` et `PageManager`
   - correction d'un souci de détection des inclusions récursives des groupes ACL
 - correction du bouton "connecter" dans le menu déroulant à partir de `doryphore 4.4.3`
 - ne pas activer les langues inconnues à partir de `doryphore 4.4.3`

### Actions

Quelques actions sympathiques indépendantes comme :
 - `{{listformmeta}}` qui permet d'afficher la liste des formulaires du site en les reliant aux pages qui les utilisent et en idenfiant les formulaires vides ou reliées entre eux. _C'est pratique pour faire le tri dans les formulaires devenus inutiles._
 - `{{editentrypartial}}` qui permet d'avoir un formulaire pour modifier des fiches sur certains champs sélectionnées. _C'est utile pour envoyer un lien aux usagers pour mettre à jour une information sans les noyer dans la relecture de leur fiche en entier (cas des formulaires longs)._ Aide détaillée [ci-dessous](#aide-pour-l39action-editentrypartial)
 - Amélioration de l'**action `{{video}}`** pour permettre une syntaxe plus simple ``{{video url="..."}}` (configurable via composants)
 - `{{autoupdateareas}}` qui permet de générer automatiquement une liste des départements et régions français. _C'est pratique pourl'extension `twolevels` (cf. documentation ci-dessous)_
 - `{{breadcrumbs}}` qui permet de générer un menu de navigation de type fil d'Ariane

### Champs bazar

Ajout des champs:
  - modification du champ `lien_internet` pour permettre l'affichage direct d'une video dans une fiche
  - `sendmailselector` pour permettre de choisir à qui envoyer une copie de la fiche, choix parmi une liste. _Pratique pour que la copie de la fiche soit envoyé au bon référent local_
  - `customsendmail` permet d'offrir à l'usager la possibilité de recevoir ou non une copie de sa fiche à chaque modification. _Pratique pour éviter l'infobésité des e-mails tout en laissant le choix à l'usager_
  - `choice-display-hidden` permet d'avoir une zone qui se déplie/replie lors de l'affichage d'une fiche. Pour l'utiliser, il faut encapsuler la zone concernée (une seule zone par fiche) dans `<div class="hidden-field-specific">/<div>` (ceci se fait à l'aide des champs `labelhtml`)
  - `subscribe` qui permet de s'inscrire à un évènement, un groupe (cf. [documentation ci-dessous](#documentation-pour-le-champ-subscribe))

### Handlers

 - Retrouver le **handler `/diff`** bien pratique pour comparer rapidement les changements dans une fiche ou une page
 - **Possibilité de dupliquer une fiche ou une page** (handler `/duplicate`)
   - pour fonctionner parfaitement, il faudrait penser à ajouter `duplicateiframe` pour le paramètre `allowed_methods_in_iframe` dans la page [`GererConfig`](?GererConfig ':ignore')
   - de plus, pour pouvoir dupliquer des fiches même si l'utilisateur n'a pas les droits de création d'un page, il faut modifier à `true` le paramètre `canDuplicateEntryIfNotRightToWrite` dans la page [GererConfig](?GererConfig ':ignore'), partie `ALTERNATIVEUPDATEJ9REM`

### Templates bazar
 
 - Un **nouveau template dynamique** `{{bazarliste template="video"}}` qui permet d'afficher les fiches en vignettes comme pour le template dynamique `card` mais en affichant les vidéos des fiches.
 - Un **template dynamique** `{{bazarliste template="send-mail"}}` qui permet d'afficher d'envoyer des e-mails de façon groupée. _Cette fonctionnalité est désactivée par défaut. Il faut se rendre dans [GererConfig](?GererConfig ':ignore'), dans la partie `ALTERNATIVEUPDATEJ9REM`, et mettre `true` pour la variable `sendMail[activated]` pour l'activer._

### Nouvelles fonctionnalités

 - un système de cache local (cf. [Aide](#cache-local))
 - **La possibilité de gérer des évènements récurrents** (cf. [Aide](#%C3%89v%C3%A8nements-r%C3%A9currents))
 - possibilité de supprimer toutes les pages et fiches possédées par un utilisateur lors de la suppression de cet utilisateur (à activer dans [GererConfig](?GererConfig ':ignore'))
 - **La possibilité de connecter un formulaire à [Open Agenda](https://openagenda.com)** (cf. documentation [ci-dessous](#documentation-open-agenda))
 - possibilité de forcer la sauvegarde d'une fiche incomplète pour les membres d'un groupe spécifique
 - ajout d'une option pour garder l'ordre des listes dans les filtres bazar
 - option pour masquer le lien vers la page "MesContenus"
 - option pour éditer la fiche plutôt que l'afficher si une seule fiche par formulaire
 - ajouter des réglages pour les performances `SQL`

----
**Identification des fonctionnalités dans le code source**

|**Type**|**Nom**|**Identifiant**|
|:-|:-|:-|
|Action|`{{autoupdateareas}}`|[`auj9-autoupdateareas-action`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-autoupdateareas-action&type=code)|
||`{{editentrypartial}}`|[`auj9-editentrypartial-action`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-editentrypartial-action&type=code)|
||`{{listformmeta}}`|[`auj9-listformmeta-action`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-listformmeta-action&type=code)|
||`{{breadcrumbs}}`|[`auj9-breadcrumbs-action`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-breadcrumbs-action&type=code)|
|Champ bazar|`customsendmail`|[`auj9-custom-sendmail`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-custom-sendmail&type=code)|
||`choice-display-hidden`|[`auj9-choice-display-hidden-field`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-choice-display-hidden-field&type=code)|
||`lien_internet` pour les vidéos|[`auj9-video-field`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-video-field&type=code)|
||`sendmailselector`|[`auj9-send-mail-selector-field`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-send-mail-selector-field&type=code)|
||`subscribe`|[`auj9-subscribe-to-entry`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-subscribe-to-entry&type=code)|
|Handler|`/diff`|[`auj9-diff`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-diff&type=code)|
||`/duplicate`|[`auj9-duplicate`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-duplicate&type=code)|
|Nouvelles fonctionnalités|évènements récurrents|[`auj9-recurrent-events`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-recurrent-events&type=code)|
||connection à OpenAgenda|[`auj9-open-agenda-connect`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-open-agenda-connect&type=code)|
||personnalisations propres à cette extension|[`auj9-custom-changes`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-custom-changes&type=code)|
||système de mises à jour|[`auj9-autoupdate-system`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-autoupdate-system&type=code)|
||système de cache local des requêtes SQL|[`auj9-local-cache`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-local-cache&type=code)|
||suppression d'un utilisateur ET des pages et fiches possédées|[`auj9-feat-user-controller-delete-own-pages`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-feat-user-controller-delete-own-pages&type=code)|
||possibilité pour un groupe spécifique de forcer la sauvegarde d'une fiche incomplète|[`auj9-can-force-entry-save-for-specific-group`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-can-force-entry-save-for-specific-group&type=code)|
||option pour garder l'ordre des listes dans les filtres bazar|[`auj9-bazarlist-filter-order`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-bazarlist-filter-order&type=code)|
||option pour masquer le lien vers la page "MesContenus"|[`auj9-can-choose-to-hide-link-to-content-page`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-can-choose-to-hide-link-to-content-page&type=code)|
||option pour éditer la fiche plutôt que l'afficher si une seule fiche par formulaire|[`auj9-option-entrycontroller-edit-instead-of-view-if-only-one`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-option-entrycontroller-edit-instead-of-view-if-only-one&type=code)|
||performances `SQL`|[`auj9-perf-sql`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-perf-sql&type=code)|
|Correctifs|corrections concernant les metadonnées (pour `4.4.1` ou +)|[`auj9-fix-edit-metadata`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-fix-edit-metadata&type=code)|
||corrections la faille de sécurité du handler `lmsraw`|[`auj9-fix-lmsraw-handler`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-fix-lmsraw-handler&type=code)|
||pour `doryphore 4.4.2`|[`auj9-fix-4-4-2`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-fix-4-4-2&type=code)|
||pour `doryphore 4.4.3`|[`auj9-fix-4-4-3`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-fix-4-4-3&type=code)|
||correction du bouton connecter à partir de `doryphore 4.4.3`|[`auj9-fix-navbar-login-btn-since-4-4-3`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-fix-navbar-login-btn-since-4-4-3&type=code)|
||ne pas activer les langues inconnues à partir de `doryphore 4.4.3`|[`auj9-keep-only-wanted-langs`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-keep-only-wanted-langs&type=code)|
|Template Bazar|`video.twig`|[`auj9-bazar-list-video-dynamic`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-bazar-list-video-dynamic&type=code)|
||`send-mail.twig`|[`auj9-bazar-list-send-mail-dynamic`](https://github.com/search?q=repo%3AJ9rem%2Fyeswiki-extension-alternativeupdatej9rem%20auj9-bazar-list-send-mail-dynamic&type=code)|

----

## Documentation sémantique

?> La documentation concernant les champs sémantique est maintenant disponible dans `YesWiki` à partir de `doryphore 4.4.2`.

**Vous pouvez y accéder** :
 - si la version de votre wiki est au minimum `doryphore 4.4.2`, [Accéder à la documentation du web sémantique en local](/docs/fr/semantic.md)
 - si votre version est plus ancienne : vous pouvez [trouver le texte en ligne](https://github.com/YesWiki/yeswiki/blob/doryphore/docs/fr/semantic.md)

----

## Documentation

#### Évènements récurrents

!> la gestion des évènements récurrents est temporairement désativée pa défaut.

>Pour l'activer:
>  - se rendre dans [GererConfig](?GererConfig ':ignore')
>  - dans la partie `ALTERNATIVEUPDATEJ9REM`, mettre `true` pour la variable `activateEventRepetition`

#### cache local 

 - La fonctionnalité de cache local est désativée par défaut. Il faut l'activer manuellement dans [GererConfig](?GererConfig ':ignore')
 - **ATTENTION, cette fonctionnalité est encore expérimentale**. Il est possible que le rafraîchissement trop tardif du cache epêche la bonne mise à jour des données affichées.
 - Le cache peut être effacé pour forcer un rafraîchissement de celui-ci en cliquant sur le lien ci-dessous
```yeswiki preview=100px
{{clearlocalcache}}
```

### Aide pour l'action editentrypartial

 - La configuration de cette action se fait en passant par le bouton composants lors de l'édition d'un page
 - Bien penser à attendre l'icône "✔" qui confirme que la modification a été validée puis mettre à jour le code (sinon la modification sera bien enregistrée dans la page mais elle ne sera pas active)

### Documentation pour le champ subscribe

Le champ `subscribe` permet de gérer une liste d'inscription à un groupe ou un évènement.

Pour ceci, il faut ajouter le champ dans le formulaire qui concerne le groupe, l'évènement.

Il existe **deux modes de fonctionnement**:
 - le mode `user` : la fiche enregistre l'identifiant de l'utilisateur qui clique sur s'enregistrer
 - le mode `fiche` : l'information est stockée dans un champ `checkboxfiche` contenu dans un autre formulaire.
   - Ce dernier formulaire doit représenter la ficher personnelle de l'utilisateur.
   - Il doit être configurer en mode "une seule fiche par utilisateur"
   - Le champ `checkboxfiche` doit pointer vers le formulaire du groupe ou de l'évènement.
   - Il faut aussi fournir au champ `subscribe` le nom de la page qui permet de créer une fiche personnelle

Concernant les droits d'accès, il faut que les fiches groupes/évènements soient accessibles en lecture/écriture à toute personne susceptible de s'inscrire.

Une astuce est de mettre tous les autres champs en droits d'écriture `%` ou `@admins`.

Ainsi, il sera possible à une personne connectée de modifier la fiche évènement de façon automatisée pour sauvegarder son inscription.

Il existe aussi la possibilité de **limiter le nombre d'inscription**. Ceci se fait en éditer le groupe/évènement concerné et en choisissant une valeur différente de `-1`. _Les inscriptions déjà faites ne seront pas automatiquement supprimées si elles sont trop nombreuses_.

Enfin, lors de l'ajout du champ `suscribe`, il y a aussi ajout du champ `nbsubscription` qui permet de sauvegarder le nombre d'inscription au groupe. La valeur de ce champ se modifie de façon automatique. Il est possible de supprimer ce champ s'il n'est pas utile pour votre formulaire.  
_Astuce : utiliser ce champ pour un tri en fonction du nombre d'inscriptions pour `bazarliste`_

### Documentation Open Agenda

!> par défaut, la fonctionnalité de connexion automatique à un calendrier Open Agenda est désactivée.

>Pour l'activer
>  - se rendre dans [GererConfig](?GererConfig ':ignore')
>  - dans la partie `ALTERNATIVEUPDATEJ9REM`, mettre `true` pour la variable `openAgenda[isActivated]`

#### Pré-requis

Pour pouvoir synchroniser automiquement les données d'un YesWiki vers un open agenda, vous devez:
 - disposer d'un compte Open Agenda : https://openagenda.com/
 - connaître l'identifiant unique de l'agenda à connecter
 - disposer des droits d'écriture sur cette agenda avec votre compte Open Agenda
 - disposer d'une clé secrète associée à votre compte Open Agenda:
   - elle est visible par ce lien si vous êtes connecté : https://openagenda.com/settings/apiKey
   - si vous n'en disposez pas, vous devez faire la demande de votre clé secrète en écrivant un mmessage à l'adresse e-mail indiquée dans ce paragraphe d'aide : https://developers.openagenda.com/00-introduction/#utilisation-en-criture
   - _la documentation associée est disponible ici : https://developers.openagenda.com/ (pour les développeurs)_

#### Réglages

La configuration la connexion à Open Agenda se fait via cette adresse sur votre wiki : [?api/openagenda/config/html](?api/openagenda/config/html ':ignore').

### Création automatique des formulaires départements et régions

Il est possible de créer automatiquement les formulaires départements et régions, très pratiques pour l'usage de l'extension `twolevels`.

#### Création automatique de la liste des départements français

Il est possible d'automatiquement créer la liste des départements français en cliquant sur le bouton ci-dessous en tant qu'administrateur du wiki. Il n'y aura plus qu'à sélectionner cette liste dans le menu déroulant du constructeur graphique de formulaire. (_Attention, si la liste existe déjà, elle n'est pas mise à jour. Il faut la supprimer au préalable._)

```yeswiki preview=100px
{{autoupdateareas type="departments"}}
```

#### Création automatique de la liste des régions françaises

Il est possible d'automatiquement créer la liste des régions françaises en cliquant sur le bouton ci-dessous en tant qu'administrateur du wiki. Il n'y aura plus qu'à sélectionner cette liste dans le menu déroulant du constructeur graphique de formulaire. (_Attention, si la liste existe déjà, elle n'est pas mise à jour. Il faut la supprimer au préalable._)

```yeswiki preview=100px
{{autoupdateareas type="areas"}}
```

#### Création automatique des fiches associant régions et département

Il est possible d'automatiquement créer le formulaire et les fiches pour associer les régions et les départements en cliquant sur le bouton ci-dessous en tant qu'administrateur du wiki. (_Attention, si le formulaire existe déjà, il n'est pas mis à jour. Il faut le supprimer au préalable._)

```yeswiki preview=100px
{{autoupdateareas type="form"}}
```
Le numéro du formulaire est stocké dans le paramètre `formIdAreaToDepartment` dans la page `GererConfig`