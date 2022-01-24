//Controllers for pages of the system, except for setting pages

app.controller('MyInfo', function ($element, $scope, $smartboards, $compile, $state) {


    $smartboards.request('core', 'getUserInfo', {}, function (data, err) {
        if (err) {
            giveMessage(err.description);
            return;
        }
        $scope.user = data.userInfo;

        myInfo = $("<div id='myInfo'></div>");
        title = $("<div class='title_container'><div class='title'>My Information</div><span class='edit_icon icon' title='Edit' value='#edit-info' onclick='openModal(this)' ng-click='modifyUser(user)'></span></div>");
        //subtitle = $("<div class='warning'>If some of the following information is not right please contact your Teacher to fix it.</div>");
        informationbox = $("<div class='information_box'></div>");
        image = $('<div class="profile_img"></div>');

        var profile_image = new Image();
        profile_image.onload = function () {
            image.append(profile_image);
        }
        profile_image.onerror = function () {
            image.append($('<span>No profile image was selected</span>'));
        }
        profile_image.src = 'photos/' + $scope.user.username + '.png?' + new Date().getTime();



        atributes = ['Name', 'Nickname', 'Student Number', 'Email', 'Authentication', 'Username']
        values = ['name', 'nickname', 'studentNumber', 'email', 'authenticationService', 'username']
        atributes_column = $("<div class='info'></div>");
        values_column = $("<div class='info'></div>");
        jQuery.each(atributes, function (index) {
            atributes_column.append($('<span class="label">' + atributes[index] + '</span>'));
            if ($scope.user[values[index]] == null) {
                values_column.append($('<span class="not_set">Not set</span>'));
            } else {
                values_column.append($('<span>' + $scope.user[values[index]] + '</span>'));
            }

        });
        informationbox.append(image);
        informationbox.append(atributes_column);
        informationbox.append(values_column);

        //edit info modal
        editmodal = $("<div class='modal' id='edit-info'></div>");
        editUser = $("<div class='modal_content'></div>");
        editUser.append($('<button class="close_btn icon" value="#edit-info" onclick="closeModal(this)"></button>'));
        editUser.append($('<div class="title">Edit Info: </div>'));
        editcontent = $('<div class="content">');
        editbox = $('<div id="edit_box" class= "inputs">');
        editrow_inputs = $('<div class= "row_inputs"></div>');
        //image input
        editrow_inputs.append($('<div class="image smaller"><div style="height:300px;"><div class="profile_image"><div id="edit_display_profile_image"></div></div><input type="file" class="form__input" id="edit_profile_image" required="" accept=".png, .jpeg, .jpg"/></div></div>'));
        //text inputs
        editdetails = $('<div class="details bigger right"></div>');
        editdetails.append($('<div class="container" ><input type="text" class="form__input" id="name" placeholder="Name" ng-model="editUser.userName" disabled="disabled"/> <label for="name" class="form__label">Name</label></div>'));
        editdetails.append($('<div class="container" ><input type="text" class="form__input" id="nickname" placeholder="Nickname" ng-model="editUser.userNickname"/><label for="nickname" class="form__label">Nickname</label></div>'));
        editdetails.append($('<div class="container" ><div class="container"><input type="text" class="form__input" id="studentNumber" placeholder="Student Number" ng-model="editUser.userStudentNumber" disabled="disabled"/><label for="studentNumber" class="form__label">Number</label></div></div>'));
        editdetails.append($('<div class="container" ><input type="text" class="form__input" id="email" placeholder="Email *" ng-model="editUser.userEmail"/><label for="email" class="form__label">Email</label></div>'));
        editdoubledetails = $('<div class="container" >');
        editdoubledetails.append($('<div class="details half"><div class="container" ><input type="text" class="form__input" id="studentNumber" placeholder="Auth service" ng-model="editUser.userAuthService" disabled="disabled"/><label for="authService" class="form__label">Auth</label></div></div>'));
        editdoubledetails.append($('<div class="details half right"><div class="container" ><input type="text" class="form__input" id="major" placeholder="Username" ng-model="editUser.userUsername" disabled="disabled"/><label for="username" class="form__label">Username</label></div></div>'));
        editdetails.append(editdoubledetails);
        editrow_inputs.append(editdetails);
        editbox.append(editrow_inputs);
        // authentication information - service and username

        editcontent.append(editbox);
        editcontent.append($('<button class="save_btn" ng-click="submitEditUser()" ng-disabled="!isReadyToEdit()" > Save </button>'));
        editUser.append(editcontent);
        editmodal.append(editUser);

        myInfo.append(title);
        myInfo.append(informationbox);
        // myInfo.append(subtitle);
        myInfo.append(editmodal);
        $compile(myInfo)($scope);
        $element.append(myInfo);

        $scope.modifyUser = function (user) {
            $scope.editUser = {};
            $scope.editUser.userId = user.id;
            $scope.editUser.userName = user.name;
            $scope.editUser.userEmail = user.email;
            $scope.editUser.userMajor = user.major;
            $scope.editUser.userStudentNumber = user.studentNumber;
            $scope.editUser.userNickname = user.nickname;
            $scope.editUser.userUsername = user.username;
            $scope.editUser.userAuthService = user.authenticationService;
            $scope.editUser.userImage = null;
            $scope.editUser.userHasImage = "false";


            var imageInput = document.getElementById("edit_profile_image");
            var imageDisplayArea = document.getElementById("edit_display_profile_image");
            imageDisplayArea.innerHTML = "";
            //set initial image
            var profile_image = new Image();
            profile_image.onload = function () {
                imageDisplayArea.appendChild(profile_image);
            };
            profile_image.onerror = function () {
                $("#edit_display_profile_image").append($("<span>Select a profile image</span>"));
            };
            profile_image.src = "photos/" + user.username + ".png?" + new Date().getTime();
            //set listener for input change
            imageInput.addEventListener("change", function (e) {
                var file = imageInput.files[0];
                var imageType = /image.*/;
                if (file.type.match(imageType)) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        imageDisplayArea.innerHTML = "";
                        var img = new Image();
                        img.src = reader.result;
                        $scope.editUser.userImage = reader.result;
                        $scope.editUser.userHasImage = "true";
                        imageDisplayArea.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    $("#display_profile_image").empty();
                    $("#display_profile_image").append($("<span>Please choose a valid type of file (hint: image)</span>"));
                    $scope.editUser.userImage = null;
                    $scope.editUser.userHasImage = "false";
                }
            });

            $scope.isReadyToEdit = function () {
                isValid = function (text) {
                    return text != "" && text != undefined && text != null;
                };
                //validate inputs
                if (isValid($scope.editUser.userName) && isValid($scope.editUser.userEmail) && isValid($scope.editUser.userStudentNumber) && isValid($scope.editUser.userUsername) && isValid($scope.editUser.userAuthService)) {
                    return true;
                } else {
                    return false;
                }
            };

            $scope.submitEditUser = function () {
                var reqData = {
                    course: $scope.course,
                    userName: $scope.editUser.userName,
                    userId: $scope.editUser.userId,
                    userStudentNumber: $scope.editUser.userStudentNumber,
                    userNickname: $scope.editUser.userNickname,
                    userEmail: $scope.editUser.userEmail,
                    userUsername: $scope.editUser.userUsername,
                    userAuthService: $scope.editUser.userAuthService,
                    userImage: $scope.editUser.userImage,
                    userHasImage: $scope.editUser.userHasImage,
                };

                $smartboards.request("core", "editSelfInfo", reqData, function (data, err) {
                    if (err) {
                        giveMessage(err.description);
                        return;
                    }
                    $("#edit-info").hide();
                    //getUsers();
                    window.location.reload();
                    // $("#action_completed").append("User: " + $scope.editUser.userName + "-" + $scope.editUser.userStudentNumber + " edited");
                    //$("#action_completed").show().delay(3000).fadeOut();
                });
            };
        };
    });

});
