import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {ResourceManager} from "../../../_utils/resources/resource-manager";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";
import {exists} from "../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";

@Component({
  selector: 'app-file-picker-modal',
  templateUrl: './file-picker-modal.component.html',
  styleUrls: ['./file-picker-modal.component.scss']
})
export class FilePickerModalComponent implements OnInit {

  @Input() id?: string;                                 // Modal id
  @Input() type: string;                                // File type to pick from
  @Input() courseFolder: string;                        // Course data folder path (where to look for images)
  @Input() whereToStore: string;                        // Folder path of where to store images (relative to course folder)
  @Input() classList?: string;                          // Classes to append

  @Input() isModalOpen: boolean;                        // Whether the modal is visible
  @Input() actionInProgress?: boolean;                  // Show loader while action in progress
  @Input() innerClickEvents: boolean = true;            // Whether to close the modal when clicking outside

  @Input() positiveBtnText: string;                     // Positive btn text
  @Input() negativeBtnText: string = 'Cancel';          // Negative btn text

  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() positiveBtnClicked: EventEmitter<{path: string, type: 'image' | 'video' | 'audio'}> = new EventEmitter();
  @Output() negativeBtnClicked: EventEmitter<void> = new EventEmitter();

  readonly imageExtensions = ['.png', '.jpg', '.jpeg', '.gif'];
  readonly videoExtensions = ['.mp4', '.mov', '.wmv', '.avi', '.avchd', '.webm', '.mpeg-2'];
  readonly audioExtensions = ['.mp3', '.mpeg', '.wav', '.wave', '.mid', '.midi'];

  fileToUpload: File;
  fileToUploadName: string;

  file: string | ArrayBuffer;
  fileType: 'image' | 'video' | 'audio';

  mode: 'upload' | 'browse' = 'upload';

  loading: boolean;
  contents: ContentItem[];
  path: string;

  constructor(
    private api: ApiHttpService
  ) { }

  get ContentType(): typeof ContentType {
    return ContentType;
  }

  get ApiEndpointsService(): typeof ApiEndpointsService {
    return ApiEndpointsService;
  }

  ngOnInit(): void {
    this.path = this.courseFolder;
    this.getContents();
  }

  onFileSelected(files: FileList): void {
    this.fileToUpload = files.item(0);
  }

  getContents() {
    this.loading = true;
    const courseID = parseInt(this.courseFolder.split('/')[1].split('-')[0]);
    this.api.getCourseDataFolderContents(courseID)
      .pipe(finalize(() => this.loading = false))
      .subscribe(contents => this.contents = contents);
  }

  getFolderContents(folder: any, path: string): ContentItem[] {
    path = path.removeWord(this.courseFolder);
    if (path[0] === '/') path = path.substr(1);

    if (path === '')
      return this.filterItems(folder.hasOwnProperty('files') ? folder.files : folder, this.type);

    const split = path.split('/');
    const f = folder.find(el => el.name === split[0]);
    return this.getFolderContents(f.files, split.length === 1 ? '' : split.slice(1).join('/'));
  }

  filterItems(items: ContentItem[], type: string): ContentItem[] {
    let files = [];
    if (type.containsWord('image'))
      files = files.concat(items.filter(item => item.filetype === ContentType.FILE && this.imageExtensions.includes(item.extension.toLowerCase())));

    if (type.containsWord('video'))
      files = files.concat(items.filter(item => item.filetype === ContentType.FILE &&  this.videoExtensions.includes(item.extension.toLowerCase())));

    if (type.containsWord('audio'))
      files = files.concat(items.filter(item => item.filetype === ContentType.FILE &&  this.audioExtensions.includes(item.extension.toLowerCase())));

    return files.concat(items.filter(item => item.filetype === ContentType.FOLDER));
  }

  goInside(item: ContentItem) {
    if (item.filetype !== ContentType.FOLDER) {
      this.selectItem(item);
      return;
    }
    this.path += '/' + item.name;
  }

  goOutside() {
    if (this.path === this.courseFolder) return;
    const split = this.path.split('/');
    this.path = split.slice(0, split.length - 1).join('/');
  }

  selectItem(item: ContentItem) {
    this.file = this.path + '/' + item.name;
    this.fileType = this.imageExtensions.includes(item.extension.toLowerCase()) ? 'image' :
      this.videoExtensions.includes(item.extension.toLowerCase()) ? 'video' : 'audio';
  }

  async submit() {
    if (this.fileToUpload) {
      // Save file in server
      await ResourceManager.getBase64(this.fileToUpload).then(data => this.file = data);
      const courseID = parseInt(this.courseFolder.split('/')[1].split('-')[0]);
      this.api.uploadFileToCourse(courseID, this.file, this.whereToStore, this.fileToUploadName)
        .subscribe(
          path => {
            this.positiveBtnClicked.emit({path, type: this.fileToUpload.type.split('/')[0] as 'image' | 'video' | 'audio'});
            this.closeBtnClicked.emit();
            this.reset();
          })

    } else {
      this.positiveBtnClicked.emit({path: this.file as string, type: this.fileType});
      this.closeBtnClicked.emit();
      this.reset();
    }
  }

  isReadyToSubmit(): boolean {
    return (exists(this.fileToUpload) && exists(this.fileToUploadName) && !this.fileToUploadName.isEmpty()) ||
      exists(this.file);
  }

  reset() {
    this.fileToUpload = null;
    this.fileToUploadName = null;
    this.file = null;
    this.fileType = null;
  }

  getItemIcon(item: ContentItem): string {
    if (item.filetype === ContentType.FOLDER)
      return 'assets/icons/folder.svg';

    if (this.imageExtensions.includes(item.extension.toLowerCase()))
      return ApiEndpointsService.API_ENDPOINT + '/' + this.path + '/' + item.name;

    if (this.videoExtensions.includes(item.extension.toLowerCase()))
      return 'assets/icons/file-video.svg';

    if (this.audioExtensions.includes(item.extension.toLowerCase()))
      return 'assets/icons/file-audio.svg';

    return 'assets/icons/file.svg';
  }

}

enum ContentType {
  FILE = 'file',
  FOLDER = 'folder'
}

export interface ContentItem {
  name: string,
  filetype: ContentType,
  files?: ContentItem[],
  extension?: string
}
