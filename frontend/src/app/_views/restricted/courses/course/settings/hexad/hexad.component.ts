import {Component, OnInit} from "@angular/core";
@Component({
  selector: 'app-hexad',
  templateUrl: './hexad.component.html'
})
export class HexadComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  ngOnInit(): void {
    this.loading.page = false;
  }

}