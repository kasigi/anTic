angular.module('anTicketer', ['ngRoute']).
config(function($routeProvider){

    $routeProvider
    // route for the contact page
        .when('/', {
            templateUrl : 'partials/table.html',
            controller  : 'MainController'
        });

});

/*





 */


