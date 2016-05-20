angular.module('anTicketer', ['ngRoute']).
config(function($routeProvider){

    $routeProvider
    // route for the contact page
        .when('/table/', {
            templateUrl : 'partials/table.html',
            controller  : 'MainController',
            reloadOnSearch : false
        })
        .otherwise( { redirectTo: "/table" });

});

/*





 */


