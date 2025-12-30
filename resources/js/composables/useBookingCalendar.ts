import { ref, onMounted, type Ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

type EventClickArg = { event: any };

export interface CalendarFilterState {
  search: string;
  title: string;
  address: string;
  horses: string;
  trainers: string;
}

type Options = {
  compact?: boolean;
  eventClick?: (arg: EventClickArg) => void;
  // Allow callers to override event source when needed
  eventsUrl?: string;
  filters?: Ref<CalendarFilterState>;
};

export function useBookingCalendarOptions(opts: Options = {}) {
  const calendarRef = ref<any>(null);
  const page = usePage();
  const publicSettings = computed(() => (page.props as any).site);

  const compact = !!opts.compact;
  const eventsUrl = opts.eventsUrl ?? '/timeslots/feed';

  const calendarOptions: any = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    timeZone: import.meta.env.VITE_TZ ?? 'UTC',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    weekends: true,
    selectable: false,
    editable: false,
    events: { url: eventsUrl },
    eventClick: opts.eventClick,
    // Sizing presets
    contentHeight: compact ? 600 : 900,
    expandRows: true,
    dayMaxEventRows: compact ? 3 : 4,
    dayMaxEvents: true,
    // Defaults; overridden by public settings when available
    slotMinTime: publicSettings.value?.booking_open_time || '09:00:00',
    slotMaxTime: publicSettings.value?.booking_close_time || '19:00:00',
    scrollTime: publicSettings.value?.booking_open_time || '09:00:00',
    businessHours: {
      startTime: publicSettings.value?.booking_open_time || '09:00:00',
      endTime: publicSettings.value?.booking_close_time || '19:00:00',
      daysOfWeek: [0, 1, 2, 3, 4, 5, 6],
    },
    height: 'auto',

    // Client-side filtering logic
    eventDidMount: (info: any) => {
      if (!opts.filters) return;

      const f = opts.filters.value;
      const props = info.event.extendedProps;
      const title = info.event.title?.toLowerCase() || '';
      const description = props.description?.toLowerCase() || '';
      const trainerLabel = props.trainer_label?.toLowerCase() || '';
      const horseLabel = props.horse_label?.toLowerCase() || '';
      const locationName = props.location_name?.toLowerCase() || '';
      const locationAddress = props.location_address?.toLowerCase() || '';
      const serviceName = props.service_name?.toLowerCase() || '';

      let visible = true;

      // Global Search
      if (f.search) {
        const s = f.search.toLowerCase();
        const matches =
          title.includes(s) ||
          description.includes(s) ||
          trainerLabel.includes(s) ||
          horseLabel.includes(s) ||
          locationName.includes(s) ||
          locationAddress.includes(s) ||
          serviceName.includes(s);

        if (!matches) visible = false;
      }

      // Per-field filters
      if (visible && f.title && !title.includes(f.title.toLowerCase())) {
        visible = false;
      }

      if (visible && f.address) {
        const a = f.address.toLowerCase();
        if (!locationName.includes(a) && !locationAddress.includes(a)) {
          visible = false;
        }
      }

      if (visible && f.horses) {
        const hs = f.horses.toLowerCase().split(',').map(s => s.trim()).filter(Boolean);
        if (hs.length > 0) {
          const matches = hs.some(h => horseLabel.includes(h));
          if (!matches) visible = false;
        }
      }

      if (visible && f.trainers) {
        const ts = f.trainers.toLowerCase().split(',').map(s => s.trim()).filter(Boolean);
        if (ts.length > 0) {
          const matches = ts.some(t => trainerLabel.includes(t));
          if (!matches) visible = false;
        }
      }

      if (!visible) {
        info.el.style.display = 'none';
      } else {
        info.el.style.display = '';
      }
    },
  };

  function updateOptions(s: any) {
    if (!s?.booking_open_time || !s?.booking_close_time) return;
    const api = calendarRef.value?.getApi?.();
    if (api) {
      api.setOption('slotMinTime', s.booking_open_time);
      api.setOption('slotMaxTime', s.booking_close_time);
      api.setOption('scrollTime', s.booking_open_time);
      api.setOption('businessHours', {
        startTime: s.booking_open_time,
        endTime: s.booking_close_time,
        daysOfWeek: [0, 1, 2, 3, 4, 5, 6],
      });
    }
  }

  onMounted(() => {
    updateOptions(publicSettings.value);
  });

  watch(publicSettings, (newVal) => {
    updateOptions(newVal);
  }, { deep: true });

  return { calendarRef, calendarOptions, publicSettings };
}
