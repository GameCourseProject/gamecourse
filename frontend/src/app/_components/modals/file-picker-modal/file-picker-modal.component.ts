import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {ResourceManager} from "../../../_utils/resources/resource-manager";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {exists} from "../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";
//import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {ModalService} from "../../../_services/modal.service";
import {Observable} from "rxjs";
import {DomSanitizer} from "@angular/platform-browser";

@Component({
  selector: 'app-file-picker-modal',
  templateUrl: './file-picker-modal.component.html',
  //styleUrls: ['./file-picker-modal.component.scss']
})
export class FilePickerModalComponent implements OnInit {

  @Input() id: string;                                  // Modal id
  @Input() type: string;                                // File type to pick from
  @Input() courseFolder: string;                        // Course data folder path (where to look for images)
  @Input() whereToStore: string;                        // Folder path of where to store images (relative to course folder)
  @Input() classList?: string;                          // Classes to append

  //@Input() isModalOpen: boolean;                        // Whether the modal is visible
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

  file: string | ArrayBuffer;
  filePreview: ResourceManager;

  fileType: 'image' | 'video' | 'audio';

  loading: boolean;
  path: string;
  originalRoot: ContentItem;
  root: ContentItem;

  //contents: ContentItem[];

  // Tabs for option to upload file or browse files in system
  tabs: { name: 'upload'| 'browse', selected: boolean }[] = [{ name: 'upload', selected: true }, { name: 'browse', selected: false }];

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer,
  ) {
  }

  get ContentType(): typeof ContentType {
    return ContentType;
  }

  /*get ApiEndpointsService(): typeof ApiEndpointsService {
    return ApiEndpointsService;
  }*/

  /*** ------------------------------------------ ***/
  /*** ------------------ Init ------------------ ***/
  /*** ------------------------------------------ ***/

  async ngOnInit() {
    this.path = this.courseFolder;

    this.originalRoot = {
      name: 'root',
      type: ContentType.FOLDER,
      extension: this.path,
      contents: await this.getRootContents(),
      selected: false
    }
    this.root = this.originalRoot;

    console.log(this.root);
    ModalService.openModal('file-picker-' + this.id);
  }

  async getRootContents(): Promise<ContentItem[]> {
    this.loading = true;
    const courseID = parseInt(this.courseFolder.split('/')[1].split('-')[0]);
    let contents = await this.api.getCourseDataFolderContents(courseID, 'skills').toPromise();

    // Prepare preview photos for image files
    for (let i = 0; i < contents.length; i++){
      contents[i].selected = false;
      if (contents[i].previewUrl && this.isImage(contents[i])) {
        contents[i].previewPhoto = new ResourceManager(this.sanitizer);
        contents[i].previewPhoto.set(contents[i].previewUrl);
      }
    }

    this.loading = false;
    return contents;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ General ------------------ ***/
  /*** --------------------------------------------- ***/

  onFileSelected(files: FileList): void {
    this.fileToUpload = files.item(0);
  }

  /*getFolderContents(folder: any, path: string): ContentItem[] {
    console.log(path);
    path = path.removeWord(this.courseFolder);
    if (path[0] === '/') path = path.substr(1);

    if (path === '')
      return this.filterItems(folder.hasOwnProperty('contents') ? folder.contents : folder, this.type);

    const split = path.split('/');
    const f = folder.find(el => el.name === split[0]);
    return this.getFolderContents(f.contents, split.length === 1 ? '' : split.slice(1).join('/'));
  }

  filterItems(items: ContentItem[], type: string): ContentItem[] {
    let files = [];
    if (type.containsWord('image')){
      for (let i = 0; i < items.length; i++) {
        if (items[i].type === ContentType.FILE && this.imageExtensions.includes(items[i].extension.toLowerCase())){
          files.push(items[i]);
        }
      }
    }
      //files = files.concat(items.filter(item => item.type === ContentType.FILE && this.imageExtensions.includes(item.extension.toLowerCase())));

    if (type.containsWord('video')){
      for (let i = 0; i < items.length; i++) {
        if (items[i].type === ContentType.FILE && this.videoExtensions.includes(items[i].extension.toLowerCase())){
          files.push(items[i]);
        }
      }
    }
      //files = files.concat(items.filter(item => item.type === ContentType.FILE &&  this.videoExtensions.includes(item.extension.toLowerCase())));

    if (type.containsWord('audio')){
      for (let i = 0; i < items.length; i++) {
        if (items[i].type === ContentType.FILE && this.audioExtensions.includes(items[i].extension.toLowerCase())){
          files.push(items[i]);
        }
      }
    }
      //files = files.concat(items.filter(item => item.type === ContentType.FILE &&  this.audioExtensions.includes(item.extension.toLowerCase())));

    for (let i = 0; i < items.length; i++) {
      if (items[i].type === ContentType.FOLDER){
        files.push(items[i]);
      }
    }
    console.log(files);
    return files;
  }

  goInside(item: ContentItem) {
    if (item.type !== ContentType.FOLDER) {
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

  isReadyToSubmit(): boolean {
    return (exists(this.fileToUpload) && exists(this.fileToUpload.name) && !this.fileToUpload.name.isEmpty()) ||
      exists(this.file);
  }*/

  async submit() {
    if (this.fileToUpload) {
      // Save file in server
      await ResourceManager.getBase64(this.fileToUpload).then(data => this.file = data);
      const courseID = parseInt(this.courseFolder.split('/')[1].split('-')[0]);
      this.api.uploadFileToCourse(courseID, this.file, this.whereToStore, this.fileToUpload.name)
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

  reset() {
    this.fileToUpload = null;
    this.file = null;
    this.fileType = null;
    this.root = this.originalRoot;
    //ModalService.closeModal('file-picker-' + this.id);
  }

  /*getItemIcon(item: ContentItem): string {
    if (item.type === ContentType.FOLDER)
      return 'assets/icons/folder.svg';

    if (this.imageExtensions.includes(item.extension.toLowerCase()))
      return ApiEndpointsService.API_ENDPOINT + '/' + this.path + '/' + item.name;

    if (this.videoExtensions.includes(item.extension.toLowerCase()))
      return 'assets/icons/file-video.svg';

    if (this.audioExtensions.includes(item.extension.toLowerCase()))
      return 'assets/icons/file-audio.svg';

    return 'assets/icons/file.svg';
  }*/

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleItems(items: any[], index: number) {
    for (let i = 0; i < items.length; i++) {
      items[i].selected = i === index;
    }
    return items;
  }

  isImage(content: ContentItem): boolean {
    return this.imageExtensions.includes(content.extension);
  }

  isVideo(content: ContentItem): boolean {
    return this.videoExtensions.includes(content.extension);
  }

  isAudio(content: ContentItem): boolean {
    return this.audioExtensions.includes(content.extension);
  }

}

enum ContentType {
  FILE = 'file',
  FOLDER = 'folder'
}

export interface ContentItem {
  name: string,
  type: ContentType,
  extension?: string,
  previewUrl?: string,
  previewPhoto?: ResourceManager,
  selected: boolean,
  contents?: ContentItem[]
}
