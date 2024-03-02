import {Component, OnInit} from "@angular/core";
import {ApiHttpService} from "../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";

@Component({
    selector: 'app-avatars',
    templateUrl: './avatars.component.html'
})
export class AvatarsComponent implements OnInit {

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
      this.route.parent.params.subscribe(async params => {
    });
  }

}
