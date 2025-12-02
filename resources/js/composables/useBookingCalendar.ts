import { ref, onMounted } from 'vue';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

type EventClickArg = { event: any };

type Options = {
  compact?: boolean;
  eventClick?: (arg: EventClickArg) => void;
  // Allow callers to override event source when needed
  eventsUrl?: string;
};

export function useBookingCalendarOptions(opts: Options = {}) {
  const calendarRef = ref<any>(null);

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
    slotMinTime: '09:00:00',
    slotMaxTime: '19:00:00',
    scrollTime: '09:00:00',
    height: 'auto',
  };

  onMounted(async () => {
    try {
      const res = await fetch('/settings/public');
      if (!res.ok) return;
      const s = await res.json();
      if (s?.booking_open_time && s?.booking_close_time) {
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
        } else {
          calendarOptions.slotMinTime = s.booking_open_time;
          calendarOptions.slotMaxTime = s.booking_close_time;
          calendarOptions.scrollTime = s.booking_open_time;
          calendarOptions.businessHours = {
            startTime: s.booking_open_time,
            endTime: s.booking_close_time,
            daysOfWeek: [0, 1, 2, 3, 4, 5, 6],
          };
        }
      }
    } catch (e) {
      // no-op: keep defaults if settings endpoint fails
    }
  });

  return { calendarRef, calendarOptions };
}
