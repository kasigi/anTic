angular.module('anTicketer', ['ngRoute', 'ngAria']).
config(function($routeProvider){

    $routeProvider
    // route for the contact page
        .when('/table/', {
            templateUrl : 'partials/manipulateData.html',
            controller  : 'TableController',
            reloadOnSearch : false
        })
        .otherwise( { redirectTo: "/table" });

});

