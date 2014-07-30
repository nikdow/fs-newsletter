var newsletterAdmin = angular.module('newsletterAdmin', []);

newsletterAdmin.controller('newsletterAdminCtrl', ['$scope', '$timeout',
    function( $scope, $timeout ) {
        
        $ = jQuery;
        
        $scope.data = _main;
        $scope.returns = _returns;
        $scope.userModified = false;
        
        $scope.change = function() {
            $scope.userModified = true;
        };
        
        $scope.sendNewsletter = function() {
            if ( $scope.userModified ) {
                alert ( 'Save newsletter before sending it.' );
                return;
            }
            if ( ! confirm( 'Are you sure?  This will send to all recipients!' ) ) return;
            $.post( $scope.data.ajax_url, $scope.returns, function( response ) {
                var ajaxdata = $.parseJSON(response);
            });
        };
        
    }
]);