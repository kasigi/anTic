angular.module('anTic', ['ngRoute', 'ngAria','ui.bootstrap'])
    .config(function($routeProvider){

    $routeProvider
    // route for the contact page
        .when('/login/', {
            templateUrl : 'partials/login.html',
            controller  : 'LoginController',
            reloadOnSearch : false
        })
        .when('/table/', {
            templateUrl : 'partials/manipulateData.html',
            controller  : 'TableController',
            reloadOnSearch : false,
            resolve: {
                factory: checkRouting
            }
        })
        .when('/system/', {
            templateUrl : 'partials/system.html',
            controller  : 'SystemController',
            reloadOnSearch : false
        })
        .otherwise( { redirectTo: "/login" });

});



var checkRouting= function ($q, $rootScope, $location,$http) {
    if ($rootScope.userMeta) {
        return true;
    } else {
        var deferred = $q.defer();

        var crData = {};
        crData.action = "whoami";

        var responsePromise = $http.post("interface/user.php", crData);
        responsePromise.success(function (data, status, headers, config) {
            console.log("checkrouting logged in");
            if(data['status'] == "Not logged in."){
                deferred.reject();
                $rootScope.userMeta = null;
                $location.path("/login/");
            }else{
                $rootScope.userMeta = data['data'];
                deferred.resolve(true);
            }
        });
        responsePromise.error(function (data, status, headers, config) {
            console.log("checkrouting not logged in");
            deferred.reject();
            $rootScope.userMeta = null;
            $location.path("/login/");
        });



        return deferred.promise;
    }
};




