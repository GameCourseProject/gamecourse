import {Component, OnInit} from "@angular/core";
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {User} from "../../../../_domain/users/user";
import {Colors, SelectedTypes} from "../../../../_components/avatar-generator/model";
import {AlertService, AlertType} from "../../../../_services/alert.service";
import {UpdateService, UpdateType} from "../../../../_services/update.service";

@Component({
    selector: 'app-avatar',
    templateUrl: './avatar.component.html'
})
export class AvatarComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  user: User;
  selected: SelectedTypes;        // Saved avatar parts (hair, clothing type, etc.)
  colors: Colors;                 // Saved avatar colors

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private updateManager: UpdateService
  ) { }

  async ngOnInit(): Promise<void> {
    this.route.parent.params.subscribe(async params => {
      await this.getLoggedUser();
      await this.getUserAvatar(this.user.id);
      this.loading.page = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();
  }

  async getUserAvatar(userID: number): Promise<void> {
    const data = await this.api.getUserAvatarSettings(userID).toPromise();
    this.selected = data.selected;
    this.colors = data.colors;
  }

  async saveUserAvatar(event: { selected: SelectedTypes; colors: Colors, base64: string }): Promise<void> {
    this.loading.action = true;

    await this.api.saveUserAvatar(this.user.id, event.selected, event.colors, event.base64).toPromise();
    // Trigger image change
    this.updateManager.triggerUpdate(UpdateType.AVATAR);

    this.loading.action = false;
    AlertService.showAlert(AlertType.SUCCESS, "Your avatar has been saved!")
  }

}
