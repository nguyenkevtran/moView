(function() {
  'use strict';
  angular.module('MoviewControllers')
    .controller('HomeController', ['$scope', '$rootScope', '$state', '$http', '$timeout', '$uibModal', '$anchorScroll', '$location', 'UserService', 'MovieService', function($scope, $rootScope, $state, $http, $timeout, $uibModal, $anchorScroll, $location, UserService, MovieService) {
      $rootScope.$pageFinishedLoading = false;
      // create slide
      $scope.myInterval = 3000;
      $scope.noWrapSlides = false;
      $scope.active = 0;
      $scope.slides = [];
      var currIndex = 0;

      $scope.$state = $state;
      $scope.isActive = false;

      $scope.refreshMovie = function() {
        MovieService.getMovieList(function(res) {
          if (res && res.meta.code <= 200) {
            $scope.movies = res.body;
            angular.forEach($scope.movies, function(movie) {
              $scope.slides.push({
                image: movie.banner,
                id: currIndex++,
                movie_id: movie.id
              });
            })
            $scope.chunkedMovies = chunk($scope.movies, 2);
            $timeout(function() {
              $rootScope.$pageFinishedLoading = true;
            }, 1000);
          }
        })
      };
      $scope.refreshMovie();

      $scope.activeButton = function() {
        $scope.isActive = !$scope.isActive;
      };

      function chunk(arr, size) {
        var newArr = [];
        for (var i = 0; i < arr.length; i += size) {
          newArr.push(arr.slice(i, i + size));
        }
        return newArr;
      }

      $scope.viewMovieDetail = function(id) {
        $state.go('index.movie', { 'id': id });
      };
    }])
    .controller('LoginController', ['$scope', '$http', '$uibModal', '$uibModalInstance', 'UserService', 'signUp', function($scope, $http, $uibModal, $uibModalInstance, UserService, signUp) {
      if (signUp) {
        $scope.up = signUp;
      }
      $scope.user = {
        'username': '',
        'password': null
      };
      $scope.signUp = function() {
        UserService.signUp($scope.user, function(res) {
          if (res && res.meta.code <= 200) {
            $scope.login();
          }
        })
      };
      $scope.login = function() {
        UserService.login($scope.user, function(res) {
          if (res && res.meta && res.meta.code <= 300) {
            $scope.errorMessage = '';
            $uibModalInstance.close(res.body.user);
          } else if (res && res.meta && res.meta.code >= 500) {
            $scope.errorMessage = 'Server Error';
          } else if (res && res.meta.code >= 400) {
            $scope.errorMessage = res.meta.message;
          }
        })
      };
      $scope.cancel = function() {
        $uibModalInstance.dismiss('cancel');
      };
    }]);
}());
