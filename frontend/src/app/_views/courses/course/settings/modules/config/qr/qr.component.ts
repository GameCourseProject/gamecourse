import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {TypeOfClass} from "../../../../page/page.component";

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

  tables: {
    participation: {
      loading: boolean,
      headers: string[],
      data: string[][]
    },
    qrError: {
      loading: boolean,
      headers: string[],
      data: string[][]
    }
  } = {
    participation: {
      loading: true,
      headers: null,
      data: null
    },
    qrError: {
      loading: true,
      headers: null,
      data: null
    }
  }

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.buildParticipationTable();
      this.buildQRErrorTable();
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
        },
        error => ErrorService.set(error)
      )
  }

  printQRCodes() {
    this.loading = true;

    const myWindow = window.open('', 'PRINT');
    const codes = document.getElementsByClassName("code");

    // Divide codes into pages
    const maxPerPage = 16;
    for (let i = 0; i < this.quantity; i += maxPerPage) {
      // Create grid
      const div = document.createElement("div");
      div.classList.add("qr-codes");
      div.style.display = "grid";
      div.style.gridTemplateColumns = "25% 25% 25% 25%";
      div.style.pageBreakInside = "avoid";

      // Add codes
      for (let j = i; j < (i + maxPerPage > this.quantity ? this.quantity : i + maxPerPage); j++) {
        const code = codes[j].cloneNode(true) as HTMLElement;
        code.style.display = "flex";
        code.style.flexDirection = "column";
        code.style.alignItems = "center";
        code.style.width = "calc(100vw / 4)";
        code.style.marginBottom = "25px";
        code.style.marginLeft = "35px";
        code.style.marginRight = "36px";
        code.style.wordBreak = "break-all";
        (code.children[1] as HTMLElement).style.fontSize = "14px";
        div.append(code);
      }

      myWindow.document.body.append(div);
    }

    myWindow.focus();
    myWindow.print();

    myWindow.onafterprint = () => myWindow.close();

    this.loading = false;
  }

  buildParticipationTable() {
    this.tables.participation.headers = [
      'id', 'name', 'student nr', 'type', 'lecture nr', 'date'
    ];

    this.api.getTableData(this.courseID, 'participation')
      .pipe(finalize(() => this.tables.participation.loading = false))
      .subscribe(
        data => {
          const QRParticipations = data.entries.filter(entry => entry.type === 'participated in lecture' || entry.type === 'participated in lecture (invited)');
          this.tables.participation.data = QRParticipations.map(entry => [
            entry.id, entry.name, entry.studentNumber,  entry.type === 'participated in lecture' ? TypeOfClass.LECTURE : TypeOfClass.INVITED_LECTURE, entry.description, entry.date
          ]);
        },
          error => ErrorService.set(error)
      )
  }

  buildQRErrorTable() {
    this.tables.qrError.headers = [
      'date', 'name', 'student nr', 'error', 'QR key'
    ];

    this.api.getTableData(this.courseID, 'qr_error')
      .pipe(finalize(() => this.tables.qrError.loading = false))
      .subscribe(
        data => {
          this.tables.qrError.data = data.entries.map(entry => [
            entry.date, entry.name, entry.studentNumber, entry.msg, entry.qrkey
          ]);
        },
        error => ErrorService.set(error)
      )
  }

}
