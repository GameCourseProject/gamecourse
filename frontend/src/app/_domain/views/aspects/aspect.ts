export class Aspect {
  private _viewerRole: string;
  private _userRole: string;

  constructor(viewerRole: string, userRole: string) {
    this._viewerRole = viewerRole;
    this._userRole = userRole;
  }


  get viewerRole(): string {
    return this._viewerRole;
  }

  set viewerRole(value: string) {
    this._viewerRole = value;
  }

  get userRole(): string {
    return this._userRole;
  }

  set userRole(value: string) {
    this._userRole = value;
  }


  static fromDatabase(obj: AspectDatabase): Aspect {
    return new Aspect(obj.viewerRole, obj.userRole);
  }

  static toDatabase(obj: Aspect): AspectDatabase {
    return { viewerRole: obj.viewerRole, userRole: obj.userRole };
  }
}

export interface AspectDatabase {
  viewerRole: string,
  userRole: string
}
