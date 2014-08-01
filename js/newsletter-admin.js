var newsletterAdmin = angular.module('newsletterAdmin', []);

newsletterAdmin.controller('newsletterAdminCtrl', ['$scope', '$timeout',
    function( $scope, $timeout ) {
        
        $ = jQuery;
        
        $scope.data = _main;

        $scope.sendNewsletter = function() {
            if ( ! confirm( 'Are you sure?  This will send to all recipients!' ) ) return;
            var data = $('#post').serialize();
            $.post( $scope.data.ajax_url, data, function( response ) {
                var ajaxdata = $.parseJSON(response);
            });
        };
        
    }
]);