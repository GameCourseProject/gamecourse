var app = angular.module('Test', ['smartBoard'], function () {

});

app.controller('TestController', function ($scope, $smartboards, $element, $compile) {
    $smartboards.request('views', 'getEdit', {course: 0, view: 'overview'}, function(data, err) {
        if (err) {
            console.log(err);
            return
        }

        $scope.fields = data.fields;
        $element.append($compile($('<sb-field sb-field="part.info" sb-field-label="Field"></sb-field>'))($scope));
    });
});

app.directive('sbFieldTreeNode', function($parse, $compile) {
    return {
        replace: true,
        restrict: 'E',
        scope: true,
        link: function ($scope, element, attrs) {
            $scope.field = $parse(attrs.sbFieldTreeNode)($scope);
            if ($scope.field.type == undefined) {
                element.append('Root');
                for (var field in $scope.field) {
                    element.append($compile($('<sb-field-tree-node sb-field-tree-node="field.' + field + '"></sb-field-tree-node>'))($scope));
                }
            } else {
                var types = ['Field', 'Array', 'Object', 'Map'];
                element.append($scope.field.field + ' - ' + types[$scope.field.type] + ' - ' + $scope.field.example);
                if ($scope.field.type == 0) {
                } else if ($scope.field.type == 1) {
                    element.append($compile($('<sb-field-tree-node sb-field-tree-node="field.value"></sb-field-tree-node>'))($scope));
                } else if ($scope.field.type == 2) {
                    for (var field in $scope.field.fields) {
                        element.append($compile($('<sb-field-tree-node sb-field-tree-node="field.fields.' + field + '"></sb-field-tree-node>'))($scope));
                    }
                } else if ($scope.field.type == 3) {
                    element.append($compile($('<sb-field-tree-node sb-field-tree-node="field.options.value"></sb-field-tree-node>'))($scope));
                }
            }
        },
        template: '<div style="margin-left: 10px"></div>'
    }
}).directive('sbField', function($parse) {
    var uid = 0;
    return {
        replace: true,
        restrict: 'E',
        scope: true,
        transclude: true,
        link: function ($scope, element, attrs) {
            var parsedValue = $parse(attrs.sbField);
            $scope.value = parsedValue($scope);
            $scope.label = attrs.sbFieldLabel;
            $scope.elid = 'ip-' + (++uid);
        },
        template: '<div class="sb-component"><label for="{{elid}}">{{label}}</label><span ng-model="value.field"></span><img src="images/skills.svg"><sb-field-tree-node sb-field-tree-node="fields"></sb-field-tree-node><div class="content" ng-transclude></div></div>'
    }
});