import { Component } from '@angular/core';
import {ErrorService} from "./_services/error.service";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent {
  title = 'gamecourse-v2';

  constructor() {
  }

  hasError(): boolean {
    return !!ErrorService.get();
  }

  getError(): string {
    return ErrorService.get();
  }

  clearError(): void {
    ErrorService.clear();
  }


}
