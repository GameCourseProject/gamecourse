var app = angular.module('smartBoard', ['ui.router', 'oc.lazyLoad']);

app.run(['$rootScope', '$state', function ($rootScope, $state) {
    $rootScope.$on('$stateChangeSuccess', function (e, toState, toParams, fromState, fromParams) {
        removeActiveLinks();
        addActiveLinks(toState.name);
        checkNavbarLength();
    });

}]);

function removeActiveLinks() {
    var elementsFrom = $(document).find('a.active[ui-sref]');
    if (elementsFrom.length > 0)
        elementsFrom.removeClass('active');
    var elementsFromNav = $(document).find('a.focused[ui-sref]');
    if (elementsFromNav.length > 0)
        elementsFromNav.removeClass('focused');
}

function addActiveLinks(state) {
    var sliced = state.split('.');
    for (var i = 1; i <= sliced.length; i++) {
        var elementsTo;
        if (sliced.slice(0, i).join('.').includes('custom'))
            elementsTo = $(document).find('a[ui-sref*="' + sliced.slice(0, i).join('.') + '"]');
        else
            elementsTo = $(document).find('a[ui-sref="' + sliced.slice(0, i).join('.') + '"]');
        if (elementsTo.length > 0)
            elementsTo.addClass('active');
        elementsTo.addClass('focused');
    }
}


app.service('$smartboards', function ($http, $q, $ocLazyLoad, $rootScope) {
    var $smartboards = this;
    this.sendFile = function (module, request, fileBuffer, callback) {
        $http.post('info.php?uploadFile&module=' + module + '&request=' + request, fileBuffer, {
            transformRequest: [],
            headers: { 'Content-Type': 'application/octet-stream' }
        }).then(function (response) {
            if (callback)
                callback(response.data.data, undefined);
        }, function (response) {
            if (callback)
                callback(undefined, { status: response.status, description: response.data.error });
        });
    };

    this.request = function (module, request, data, callback) {
        $rootScope.loaded = false;
        console.time(data.view);
        //console.log("app req",data);
        $http.post('info.php?module=' + module + '&request=' + request, data).then(function (response) {
            if (callback) {
                //console.log("after callback",response);

                if (response.data.data != undefined) {
                    callback(response.data.data, undefined);
                }
                else if (response.data != '') {
                    callback(undefined, { status: response.status, description: response.data });
                }
                else {
                    callback(undefined, undefined);
                }
                $rootScope.loaded = true;

                if (data.view == "sideview") {//para quando se faz f5 guardar o time do inicio ao fim
                    console.timeEnd(data.view);
                    console.timeEnd();
                }
                else if (data.view != null)
                    console.timeEnd(data.view);

            }
        }, function (response) {
            if (callback) {
                //console.log("error", response);
                //Reload page if session expires
                if (!("data" in response) || response.data.error == "Not logged in!") {
                    location.reload();
                }
                callback(undefined, { status: response.status, description: response.data.error });
            }
            $rootScope.loaded = true;
        });

    };

    this.loadDependencies = function (dependencies) {
        // load modules in serie..
        if (dependencies.length > 0) {
            var defer = $q.defer();
            var dep = dependencies.shift();
            //console.log('Loading module: ' + dep.name);
            //console.log(dep.files);
            while (dep != undefined && dep.files.length == 0) {
                dep = dependencies.shift();
                if (dependencies.length == 0) {
                    setTimeout(function () {
                        defer.resolve();
                    });
                    return defer.promise;
                }
            }

            $ocLazyLoad.load(dep, { serie: true }).then(function () {
                if (dependencies.length > 0)
                    $smartboards.loadDependencies(dependencies).then(function () {
                        defer.resolve.apply(this, arguments);
                    }, function () {
                        defer.reject.apply(this, arguments);
                    });
                else
                    defer.resolve.apply(this, arguments);
            }, function () {
                defer.reject.apply(this, arguments);
            });

            return defer.promise;
        } else {
            return $q(function (resolve) { resolve(); });
        }
    };
});

app.directive('bindTrustedHtml', ['$compile', function ($compile) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            scope.$watch(function () {
                return scope.$eval(attrs.bindTrustedHtml);
            }, function (value) {
                element.html(value);
                $compile(element.contents())(scope);
            });
        }
    };
}]);
