angular.module('module.skills', []);
angular.module('module.skills').controller('SkillPage', function ($scope, $smartboards, $stateParams, $compile) {
    $scope.skillName = $stateParams.skillName;
    $smartboards.request('skills', 'page', { course: $scope.course, skillName: $scope.skillName }, function (data, err) {
        if (err) {
            if (err.status == '404')
                $('#skill-description-container').text(err.description);
            else
                window.location = document.baseURI;
            return;
        }

        $scope.skill = data;
        changeTitle('Skill - ' + data.name, 1);
        $('#skill-description-container').append($compile(Builder.createPageBlock({
            'image': 'images/skills.svg',
            'text': 'Skill - {{skill.name}}'
        }, function (el) {
            el.attr('class', 'content');
            el.append($('<div>', { 'class': 'text-content', 'bind-trusted-html': 'skill.description' }));
        }))($scope));
    });
});

angular.module('module.skills').directive('skillBlock', function ($state) {
    return {
        link: function ($scope, $element) {
            $scope.gotoSkillPage = function (skill) {
                $state.go('course.skill', { 'skillName': skill.data.skillName.value });
            };

            // disable propagation of Post links
            $element.find('a').on('click', function (e) { e.stopPropagation(); });
        }
    };
});

angular.module('module.skills').config(function ($stateProvider) {
    $stateProvider.state('course.skill', {
        url: '/skill/{skillName:[A-z0-9]+}',
        views: {
            'main-view@': {
                template: '<div id="skill-description-container"></div>',
                controller: 'SkillPage'
            }
        }
    });
});

//duplicado
angular.module('module.skills').config(function ($stateProvider) {
    $stateProvider.state('course.skill', {
        url: '/skill/{skillName:[A-z0-9]+}',
        views: {
            'main-view@': {
                template: '<div id="skill-description-container"></div>',
                controller: 'SkillPage'
            }
        }
    });
});

//add config page to course
app.stateProvider.state('course.settings.skills', {
    url: '/skills',
    views: {
        'tabContent': {
            controller: 'ConfigurationController'
        }
    },
    params: {
        'module': 'skills'
    }
});



angular.module('module.skills').directive('skillStudentImage', function ($state) {
    return {
        scope: false,
        link: function ($scope, $element, $attrs) {
            $scope.gotoProfile = function (part) {
                $element.trigger('mouseout');
                $state.go('course.profile', { 'userID': part.data.student.value.id });
            };
            $scope.tooltipBound = false;
            $scope.showSkillTooltip = function (part) {
                if ($scope.tooltipBound)
                    return;
                var user = part.data.student.value;

                var tooltipContent = $('<div>', { 'class': 'content' });
                tooltipContent.append($('<img>', { 'class': 'student-image', src: 'photos/' + user.username + '.png' }));
                var tooltipUserInfo = $('<div>', { 'class': 'userinfo' });
                tooltipUserInfo.append($('<div>', { 'class': 'name', text: user.name + ' [' + user.major + ']' }));
                tooltipUserInfo.append($('<div>', { text: 'Date: ' + user.when }));
                tooltipContent.append(tooltipUserInfo);

                $element.tooltip({ offset: [-150, -65], html: tooltipContent });
                $scope.tooltipBound = true;
                $element.trigger('mouseover');
            };
        }
    };
});


Builder.onPageBlock('skills-overview', function (el, info) {
    el.addClass('skills-overview');
    for (var i = 0; i < info.content.length; ++i) {
        var tier = info.content[i];
        var tierContainer = $('<div>', { 'class': 'tier-container' });
        for (var j = 0; j < tier.skills.length; ++j) {
            var skill = tier.skills[j];
            var container = $('<div>');
            var skillSquare = $('<div>', { 'class': 'skill-square', text: skill.name + ' - ' + skill.users.length });
            skillSquare.css({ backgroundColor: skill.color });
            container.append(skillSquare);
            var photosContainer = $('<span>', { 'class': 'photos-container' });
            container.append(photosContainer);

            for (var u = 0; u < skill.users.length; ++u) {
                var user = skill.users[u];
                var userImage = $('<img>', { 'class': 'student-image', src: user.photo });

                (function (userid) {
                    userImage.click(function () {
                        $(this).closest('#overview-container').data('state').go('course.profile', { userID: userid });
                    });
                })(user.id);

                photosContainer.append(userImage);
            }

            tierContainer.append(container);
        }
        el.append(tierContainer);
    }
});

function skillsPersonalizedConfig($scope, $element, $smartboards, $compile) {

    var configPage = $($element);
    allTiers = createSection(configPage, $scope.tiers.listName);
    allTiers.attr('id', 'allTiers')
    allTiersSection = $('<div class="data-table"></div>');
    tableTiers = $('<table id="tier-table"></table>');
    rowHeaderTiers = $("<tr></tr>");
    jQuery.each($scope.tiers.header, function (index) {
        header = $scope.tiers.header[index];
        rowHeaderTiers.append($("<th>" + header + "</th>"));
    });
    rowHeaderTiers.append($("<th class='action-column'></th>")); // edit
    rowHeaderTiers.append($("<th class='action-column'></th>")); // delete
    rowHeaderTiers.append($("<th class='action-column'></th>")); // move up
    rowHeaderTiers.append($("<th class='action-column'></th>")); // move down

    rowContentTiers = $("<tr ng-repeat='(i, tier) in tiers.items' id='tier-{{tier.tier}}'> ></tr>");
    jQuery.each($scope.tiers.displayAtributes, function (index) {
        atribute = $scope.tiers.displayAtributes[index];
        stg = "tier." + atribute;
        rowContentTiers.append($('<td>{{' + stg + '}}</td>'));
    });
    rowContentTiers.append('<td class="action-column"><div class="icon edit_icon" value="#open-tier" onclick="openModal(this)" ng-click="editItem(tier)"></div></td>');
    rowContentTiers.append('<td class="action-column"><div class="icon delete_icon" value="#delete-verification-tier" onclick="openModal(this)" ng-click="deleteItem(tier)"></div></td>');
    rowContentTiers.append('<td class="action-column"><div class="icon up_icon" title="Move up" ng-click="moveUp(this)"></div></td>');
    rowContentTiers.append('<td class="action-column"><div class="icon down_icon" title="Move down" ng-click="moveDown(this)"></div></td>');

    //append table
    tableTiers.append(rowHeaderTiers);
    tableTiers.append(rowContentTiers);
    allTiersSection.append(tableTiers);
    allTiers.append(allTiersSection);
    $compile(allTiers)($scope);

    //add and edit tier modal
    modalTiers = $("<div class='modal' id='open-tier'></div>");
    open_itemTiers = $("<div class='modal_content'></div>");
    open_itemTiers.append($('<button class="close_btn icon" value="#open-tier" onclick="closeModal(this)"></button>'));
    open_itemTiers.append($('<div class="title" id="open_tier_action"></div>'));
    contentTiers = $('<div class="content">');
    boxTiers = $('<div id="new_box_tier" class= "inputs">');
    row_inputsTiers = $('<div class= "row_inputs"></div>');

    detailsTiers = $('<div class="details full config_item"></div>');
    jQuery.each($scope.tiers.allAtributes, function (index) {
        atribute = $scope.tiers.allAtributes[index];
        switch (atribute.type) {
            case 'text':
                detailsTiers.append($('<div class="half"><div class="container"><input type="text" class="form__input" placeholder="' + atribute.name + '" ng-model="openTier.' + atribute.id + '"/> <label for="' + atribute.id + '" class="form__label">' + atribute.name + '</label></div></div>'))
                break;
            case 'number':
                detailsTiers.append($('<div class="half"><div class="container"><input type="number" class="form__input"  ng-model="openTier.' + atribute.id + '"/> <label for="' + atribute.id + '" class="form__label number_label">' + atribute.name + '</label></div></div>'))
                break;
        }
    });
    row_inputsTiers.append(detailsTiers);
    boxTiers.append(row_inputsTiers);
    contentTiers.append(boxTiers);
    contentTiers.append($('<button class="cancel" value="#open-tier" onclick="closeModal(this)" > Cancel </button>'))
    contentTiers.append($('<button class="save_btn" value="#open-tier" onclick="closeModal(this)" ng-click="submitItem()"> Save </button>'))
    open_itemTiers.append(contentTiers);
    modalTiers.append(open_itemTiers);
    $compile(modalTiers)($scope);
    allTiers.append(modalTiers);


    //delete verification modal
    deletemodal = $("<div class='modal' id='delete-verification-tier'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append($('<button class="close_btn icon" value="#delete-verification-tier" onclick="closeModal(this)"></button>'));
    verification.append($('<div class="warning">Are you sure you want to delete?</div>'));
    verification.append($('<div class="target" id="delete_tier_info"></div>'));
    verification.append($('<div class="confirmation_btns"><button class="cancel" value="#delete-verification-tier" onclick="closeModal(this)">Cancel</button><button class="continue" ng-click="confirmDelete()" onclick="closeModal(this)"> Delete</button></div>'))
    deletemodal.append(verification);
    rowContentTiers.append(deletemodal);


    //success section
    allTiers.append($("<div class='success_box'><div id='action_completed' class='success_msg'></div></div>"));

    action_buttonsTier = $("<div class='action-buttons' style='width:30px;'></div>");
    action_buttonsTier.append($("<div class='icon add_icon' value='#open-tier' onclick='openModal(this)' ng-click='addItem()'></div>"));
    allTiers.append($compile(action_buttonsTier)($scope));

}