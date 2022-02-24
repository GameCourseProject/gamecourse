import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";

@Component({
  selector: 'app-qr',
  templateUrl: './qr.component.html',
  styleUrls: ['./qr.component.scss']
})
export class QrComponent implements OnInit {

  loading: boolean;
  courseID: number;

  quantity: number;
  qrCodes: {qr: string, url: string}[];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.loading = false;
    });
  }

  generateQRCodes() {
    this.loading = true;
    this.api.generateQRCodes(this.courseID, this.quantity)
      .pipe(finalize(() => this.loading = false))
      .subscribe(
        qrCodes => {
          this.qrCodes = qrCodes;
          console.log(this.qrCodes)
        },
        error => ErrorService.set(error)
      )
  }

  printQRCodes() {
    this.loading = true;

    const myWindow = window.open('', 'PRINT');

    myWindow.document.head.append(document.head.cloneNode(true));
    myWindow.document.body.innerHTML = document.getElementById('print-qr-codes').outerHTML;

    myWindow.focus();
    myWindow.print();

    myWindow.onafterprint = () => myWindow.close();

    this.loading = false;
  }

}
