<template>
  <FirstStart v-if="isFirstStart" />

  <NcContent
    app-name="memories"
    v-else
    :class="{
      'remove-gap': removeOuterGap,
      'has-nav': showNavigation,
    }"
  >
    <NcAppNavigation v-if="showNavigation">
      <template #list>
        <NcAppNavigationItem
          v-for="item in navItems"
          :key="item.name"
          :to="{ name: item.name }"
          :name="item.title"
          @click="linkClick"
          exact
        >
          <component :is="item.icon" slot="icon" :size="20" />
        </NcAppNavigationItem>
      </template>

      <template #footer>
        <ul class="app-navigation__settings">
          <NcAppNavigationItem :name="t('memories', 'Settings')" @click="showSettings">
            <CogIcon slot="icon" :size="20" />
          </NcAppNavigationItem>
        </ul>
      </template>
    </NcAppNavigation>

    <NcAppContent>
      <div
        :class="{
          outer: true,
          'router-outlet': true,
          'remove-gap': removeNavGap,
          'has-nav': showNavigation,
          'has-mobile-header': hasMobileHeader,
          'is-native': native,
        }"
      >
        <router-view />
      </div>

      <MobileHeader v-if="hasMobileHeader" />
      <MobileNav v-if="showNavigation" />
    </NcAppContent>

    <Settings :open.sync="settingsOpen" />

    <Viewer />
    <Sidebar />
    <EditMetadataModal />
    <AddToAlbumModal />
    <NodeShareModal />
    <ShareModal />
  </NcContent>
</template>

<script lang="ts">
import Vue, { defineComponent } from 'vue';

import NcContent from '@nextcloud/vue/dist/Components/NcContent';
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent';
import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation';
const NcAppNavigationItem = () => import('@nextcloud/vue/dist/Components/NcAppNavigationItem');

import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { emit, subscribe } from '@nextcloud/event-bus';

import * as utils from './services/utils';
import * as nativex from './native';
import router from './router';
import staticConfig from './services/static-config';
import UserConfig from './mixins/UserConfig';
import Timeline from './components/Timeline.vue';
import Settings from './components/Settings.vue';
import FirstStart from './components/FirstStart.vue';
import Viewer from './components/viewer/Viewer.vue';
import Metadata from './components/Metadata.vue';
import Sidebar from './components/Sidebar.vue';
import EditMetadataModal from './components/modal/EditMetadataModal.vue';
import AddToAlbumModal from './components/modal/AddToAlbumModal.vue';
import NodeShareModal from './components/modal/NodeShareModal.vue';
import ShareModal from './components/modal/ShareModal.vue';
import MobileNav from './components/MobileNav.vue';
import MobileHeader from './components/MobileHeader.vue';

import ImageMultiple from 'vue-material-design-icons/ImageMultiple.vue';
import FolderIcon from 'vue-material-design-icons/Folder.vue';
import Star from 'vue-material-design-icons/Star.vue';
import Video from 'vue-material-design-icons/PlayCircle.vue';
import AlbumIcon from 'vue-material-design-icons/ImageAlbum.vue';
import ArchiveIcon from 'vue-material-design-icons/PackageDown.vue';
import CalendarIcon from 'vue-material-design-icons/Calendar.vue';
import PeopleIcon from 'vue-material-design-icons/AccountBoxMultiple.vue';
import MarkerIcon from 'vue-material-design-icons/MapMarker.vue';
import TagsIcon from 'vue-material-design-icons/Tag.vue';
import MapIcon from 'vue-material-design-icons/Map.vue';
import CogIcon from 'vue-material-design-icons/Cog.vue';

type NavItem = {
  name: string;
  title: string;
  icon: any;
  if?: any;
};

export default defineComponent({
  name: 'App',
  components: {
    NcContent,
    NcAppContent,
    NcAppNavigation,
    NcAppNavigationItem,

    Timeline,
    Settings,
    FirstStart,
    Viewer,
    Sidebar,
    EditMetadataModal,
    AddToAlbumModal,
    NodeShareModal,
    ShareModal,
    MobileNav,
    MobileHeader,

    ImageMultiple,
    FolderIcon,
    Star,
    Video,
    AlbumIcon,
    ArchiveIcon,
    CalendarIcon,
    PeopleIcon,
    MarkerIcon,
    TagsIcon,
    MapIcon,
    CogIcon,
  },

  mixins: [UserConfig],

  data: () => ({
    navItems: [] as NavItem[],
    metadataComponent: null as any,
    settingsOpen: false,
  }),

  computed: {
    ncVersion(): number {
      const version = (<any>window.OC).config.version.split('.');
      return Number(version[0]);
    },

    native(): boolean {
      return nativex.has();
    },

    recognize(): string | false {
      if (!this.config.recognize_enabled) {
        return false;
      }

      if (this.config.facerecognition_installed) {
        return t('memories', 'People (Recognize)');
      }

      return t('memories', 'People');
    },

    facerecognition(): string | false {
      if (!this.config.facerecognition_installed) {
        return false;
      }

      if (this.config.recognize_enabled) {
        return t('memories', 'People (Face Recognition)');
      }

      return t('memories', 'People');
    },

    isFirstStart(): boolean {
      return this.config.timeline_path === 'EMPTY' && !this.routeIsPublic && !this.$route.query.noinit;
    },

    showAlbums(): boolean {
      return this.config.albums_enabled;
    },

    removeOuterGap(): boolean {
      return this.ncVersion >= 25;
    },

    showNavigation(): boolean {
      if (this.native) {
        return this.routeIsBase || this.routeIsExplore || (this.routeIsAlbums && !this.$route.params.name);
      }

      return !this.routeIsPublic;
    },

    hasMobileHeader(): boolean {
      return this.native && this.showNavigation && this.routeIsBase;
    },

    removeNavGap(): boolean {
      return this.routeIsMap;
    },
  },

  watch: {
    route() {
      this.doRouteChecks();
    },
  },

  created() {
    // No real need to unbind these, as the app is never destroyed
    const onResize = () => {
      globalThis.windowInnerWidth = window.innerWidth;
      globalThis.windowInnerHeight = window.innerHeight;
      emit('memories:window:resize', {});
    };
    window.addEventListener('resize', () => {
      utils.setRenewingTimeout(this, 'resizeTimer', onResize, 100);
    });

    // Register navigation items on config change
    subscribe(this.configEventName, this.refreshNav);

    // Register global functions
    globalThis.showSettings = () => this.showSettings();
  },

  mounted() {
    this.doRouteChecks();
    this.refreshNav();

    // Store CSS variables modified
    const root = document.documentElement;
    const colorPrimary = getComputedStyle(root).getPropertyValue('--color-primary');
    root.style.setProperty('--color-primary-select-light', `${colorPrimary}40`);
    root.style.setProperty('--plyr-color-main', colorPrimary);

    // Set theme color to default
    nativex.setTheme();

    // Register sidebar metadata tab
    globalThis.OCA?.Files?.Sidebar?.registerTab(
      new globalThis.OCA.Files.Sidebar.Tab({
        id: 'memories-metadata',
        name: this.t('memories', 'Info'),
        icon: 'icon-details',

        mount(el, fileInfo, context) {
          this.metadataComponent?.$destroy?.();
          this.metadataComponent = new Vue({
            render: (h) => h(Metadata),
            router,
          });
          this.metadataComponent.$mount(el);
          this.metadataComponent.$children[0].update(Number(fileInfo.id));
        },
        update(fileInfo) {
          this.metadataComponent.$children[0].update(Number(fileInfo.id));
        },
        destroy() {
          this.metadataComponent?.$destroy?.();
          this.metadataComponent = null;
        },
      })
    );

    // Check for native interface
    if (this.native) {
      document.documentElement.classList.add('native');
    }

    // Close navigation by default if init is disabled
    // This is the case for public folder/album shares
    if (this.$route.query.noinit) {
      emit('toggle-navigation', { open: false });
    }
  },

  async beforeMount() {
    if ('serviceWorker' in navigator) {
      // Check if dev instance
      if (window.location.hostname === 'localhost') {
        console.warn('Service Worker is not enabled on localhost.');
        return;
      }

      // Get the config before loading
      const previousVersion = staticConfig.getSync('version');

      // Use the window load event to keep the page load performant
      window.addEventListener('load', async () => {
        try {
          const url = generateUrl('/apps/memories/service-worker.js');
          const registration = await navigator.serviceWorker.register(url, {
            scope: generateUrl('/apps/memories'),
          });
          console.log('SW registered: ', registration);

          // Check for updates
          const currentVersion = await staticConfig.get('version');
          if (previousVersion !== currentVersion) {
            registration.update();
          }
        } catch (error) {
          console.error('SW registration failed: ', error);
        }
      });
    } else {
      console.debug('Service Worker is not enabled on this browser.');
    }
  },

  methods: {
    refreshNav() {
      const navItems = [
        {
          name: 'timeline',
          icon: ImageMultiple,
          title: t('memories', 'Timeline'),
        },
        {
          name: 'folders',
          icon: FolderIcon,
          title: t('memories', 'Folders'),
        },
        {
          name: 'favorites',
          icon: Star,
          title: t('memories', 'Favorites'),
        },
        {
          name: 'videos',
          icon: Video,
          title: t('memories', 'Videos'),
        },
        {
          name: 'albums',
          icon: AlbumIcon,
          title: t('memories', 'Albums'),
          if: this.showAlbums,
        },
        {
          name: 'recognize',
          icon: PeopleIcon,
          title: this.recognize || '',
          if: this.recognize,
        },
        {
          name: 'facerecognition',
          icon: PeopleIcon,
          title: this.facerecognition || '',
          if: this.facerecognition,
        },
        {
          name: 'archive',
          icon: ArchiveIcon,
          title: t('memories', 'Archive'),
        },
        {
          name: 'thisday',
          icon: CalendarIcon,
          title: t('memories', 'On this day'),
        },
        {
          name: 'places',
          icon: MarkerIcon,
          title: t('memories', 'Places'),
          if: this.config.places_gis > 0,
        },
        {
          name: 'map',
          icon: MapIcon,
          title: t('memories', 'Map'),
        },
        {
          name: 'tags',
          icon: TagsIcon,
          title: t('memories', 'Tags'),
          if: this.config.systemtags_enabled,
        },
      ];

      this.navItems = navItems.filter((item) => typeof item.if === 'undefined' || Boolean(item.if));
    },

    linkClick() {
      if (globalThis.windowInnerWidth <= 1024) {
        emit('toggle-navigation', { open: false });
      }
    },

    doRouteChecks() {
      if (this.$route.name?.endsWith('-share')) {
        this.putShareToken(<string>this.$route.params.token);
      }
    },

    putShareToken(token: string) {
      // Viewer looks for an input with ID sharingToken with the value as the token
      // Create this element or update it otherwise files not gonna open
      // https://github.com/nextcloud/viewer/blob/a8c46050fb687dcbb48a022a15a5d1275bf54a8e/src/utils/davUtils.js#L61
      let tokenInput = document.getElementById('sharingToken') as HTMLInputElement;
      if (!tokenInput) {
        tokenInput = document.createElement('input');
        tokenInput.id = 'sharingToken';
        tokenInput.type = 'hidden';
        tokenInput.style.display = 'none';
        document.body.appendChild(tokenInput);
      }

      tokenInput.value = token;
    },

    showSettings() {
      this.settingsOpen = true;
    },
  },
});
</script>

<style scoped lang="scss">
.outer {
  padding: 0 0 0 44px;
  height: 100%;
  width: 100%;

  &.remove-gap {
    padding: 0;
  }
}

@media (max-width: 768px) {
  .outer {
    padding: 0px;
  }
}

ul.app-navigation__settings {
  height: auto !important;
  overflow: hidden !important;
  padding-top: 0 !important;
  flex: 0 0 auto;
}
</style>
