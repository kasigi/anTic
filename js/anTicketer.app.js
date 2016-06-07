angular.module('anTic', ['ngRoute', 'ngAria','ui.bootstrap']).
config(function($routeProvider){

    $routeProvider
    // route for the contact page
        .when('/table/', {
            templateUrl : 'partials/manipulateData.html',
            controller  : 'TableController',
            reloadOnSearch : false
        })
        .when('/login/', {
            templateUrl : 'partials/login.html',
            controller  : 'LoginController',
            reloadOnSearch : false
        })
        .when('/system/', {
            templateUrl : 'partials/system.html',
            controller  : 'SystemController',
            reloadOnSearch : false
        })
        .otherwise( { redirectTo: "/table" });

});

