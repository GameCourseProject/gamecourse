import { Component, OnInit } from '@angular/core';
import {User} from "../../../_domain/User";
import {ApiHttpService} from "../../../_services/api/api-http.service";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  NOT_SET = 'Not set';

  attributes: string[] = ['Name', 'Nickname', 'Student Number', 'Email', 'Authentication', 'Username'];
  values = ['name', 'nickname', 'studentNumber', 'email', 'authMethod', 'username']

  user: User;
  editUser: {
    name: string,
    nickname: string,
    studentNumber: number,
    email: string,
    auth: string,
    username: string
  };

  loading = true;
  isEditModalOpen: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService
  ) { }

  ngOnInit(): void {
    this.getUserInfo();
  }

  getUserInfo(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;

        this.editUser = {
          name: user.name,
          nickname: user.nickname,
          studentNumber: user.studentNumber,
          email: user.email,
          auth: user.authMethod,
          username: user.username
        };

        this.loading = false;
      })
  }

  isReadyToEdit() {
    let isValid = function (text) {
      return text != "" && text != undefined;
    };

    // Validate inputs
    return isValid(this.editUser.username) && isValid(this.editUser.email) && isValid(this.editUser.studentNumber)
      && isValid(this.editUser.username) && isValid(this.editUser.auth);
  };

  submitEditUser(): void {
    // TODO
    // var reqData = {
    //   course: $scope.course,
    //   userName: $scope.editUser.userName,
    //   userId: $scope.editUser.userId,
    //   userStudentNumber: $scope.editUser.userStudentNumber,
    //   userNickname: $scope.editUser.userNickname,
    //   userEmail: $scope.editUser.userEmail,
    //   userUsername: $scope.editUser.userUsername,
    //   userAuthService: $scope.editUser.userAuthService,
    //   userImage: $scope.editUser.userImage,
    //   userHasImage: $scope.editUser.userHasImage,
    // };
    //
    // const formData = new FormData();
    // formData.append('course-name', rawValue.courseName);
    // formData.append('course-color', rawValue.courseColor);
    // formData.append('teacher-id', rawValue.teacherId);
    // formData.append('teacher-username', rawValue.teacherUsername);
    //
    // this.api.editSelfInfo()
    //
    // $smartboards.request("core", "editSelfInfo", reqData, function (data, err) {
    //   if (err) {
    //     giveMessage(err.description);
    //     return;
    //   }
    //   $("#edit-info").hide();
    //   //getUsers();
    //   window.location.reload();
    //   // $("#action_completed").append("User: " + $scope.editUser.userName + "-" + $scope.editUser.userStudentNumber + " edited");
    //   //$("#action_completed").show().delay(3000).fadeOut();
    // });
  }

}
